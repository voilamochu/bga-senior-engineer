<?php
namespace AGR\Cards\C;

use AGR\Core\Notifications;
use AGR\Core\Engine;
use AGR\Helpers\Utils;
use AGR\Managers\Meeples;

class C18_RollOverPlow extends \AGR\Models\MinorImprovement
{
  public function __construct($row)
  {
    parent::__construct($row);
    $this->id          = 'C18_RollOverPlow';
    $this->name        = clienttranslate('Roll-Over Plow');
    $this->deck        = 'C';
    $this->number      = 18;
    $this->isCorbariusOrDulcinaria = true;
    $this->category    = FARM_PLANNER;
    $this->desc        = [
      clienttranslate('At any time, if you have at least 3 planted fields, you can discard all goods from one of those fields to plow 1 field.'),
    ];
    $this->cost        = [ WOOD => '2' ];
  }

  public function isListeningTo($event)
  {
    if (!$this->isAnytime($event)) return false;
    return count($this->getDiscardableFields()) >= 3;
  }

  public function onPlayerAtAnytime($player, $event)
  {
    return [
      'action'   => SPECIAL_EFFECT,
      'optional' => true,
      'args'     => [
        'cardId' => $this->id,
        'method' => 'discardFieldCrops',
      ],
    ];
  }

  public function getDiscardFieldCropsDescription()
  {
    return clienttranslate('Discard all crops from a field, plow');
  }

  public function argsDiscardFieldCrops()
  {
    return [
      'cardId'            => $this->id,
      'description'       => clienttranslate('${actplayer} may discard all crops from a field to plow (Roll-Over Plow)'),
      'descriptionmyturn' => clienttranslate('Select a field to discard all crops (Roll-Over Plow)'),
      'sources'           => $this->getDiscardableFields(),
      'min'               => 1,
      'max'               => 1,
    ];
  }

  public function actDiscardFieldCrops($zones)
  {
    if (!is_array($zones) || count($zones) !== 1) {
      throw new \BgaUserException(clienttranslate('Select exactly one field.'));
    }

    $sel      = $zones[0];
    $fieldKey = $sel['id'] ?? $sel['uid'] ?? null;
    if ($fieldKey === null) {
      throw new \BgaVisibleSystemException('Invalid field selection.');
    }

    $allowed     = $this->getDiscardableFields();
    $allowedKeys = array_map(fn($z) => $z['_fieldKey'], $allowed);
    if (!in_array($fieldKey, $allowedKeys, true)) {
      throw new \BgaUserException(clienttranslate('That field is not eligible.'));
    }

    $groups = $this->groupFieldsByFieldKey();
    $group  = $groups[$fieldKey] ?? null;
    if ($group === null) {
      throw new \BgaVisibleSystemException('Selected field no longer exists.');
    }

    $meeples = $group['crops'];
    if (empty($meeples)) {
      throw new \BgaVisibleSystemException('Selected field has no crops.');
    }

    // Crop Rotation Field (E70) manual proc support
    $isCRF = (($group['repZone']['id'] ?? null) === 'E70_CropRotationField')
          || (($sel['id'] ?? null) === 'E70_CropRotationField');

    $removedTypes = array_values(array_unique(array_map(fn($m) => $m['type'] ?? null, $meeples)));

    // Remove all crops (no harvest triggers), animate as "pay from field"
    Meeples::deleteResources($meeples);

    $locationUids = $this->uidsForAnimation($group, $sel);
    Notifications::payResourcesFromFields($this->getPlayer(), $meeples, $this->name, $locationUids);

    $childs = [];

    // If the field is Crop Rotation Field, offer immediate sow
    if ($isCRF) {
      $sowType = null;

      if (in_array(GRAIN, $removedTypes, true)) {
        $sowType = VEGETABLE;
      } elseif (in_array(VEGETABLE, $removedTypes, true)) {
        $sowType = GRAIN;
      }

      if ($sowType !== null) {
        $childs[] = [
          'action'   => SOW,
          'optional' => true,
          'args'     => [
            'max'      => 1,
            'type'     => $sowType,
            'location' => ['E70_CropRotationField'],
          ],
        ];
      }
    }

    // Then the plow
    $childs[] = [
      'action'   => PLOW,
      'cardId'   => $this->id,
      'optional' => false,
      'source'   => $this->name,
    ];

    Engine::insertAsChild([
      'type'   => NODE_SEQ,
      'childs' => $childs,
    ]);
  }

  /* ===== Helpers ===== */

  private function getDiscardableFields(): array
  {
    $groups = $this->groupFieldsByFieldKey();
    $out = [];
    foreach ($groups as $k => $g) {
      if (empty($g['crops'])) continue;
      $rep = $g['repZone'];
      $rep['_fieldKey'] = $k;
      $out[] = $rep;
    }
    return $out;
  }

  private function groupFieldsByFieldKey(): array
  {
    $zones  = $this->getPlayer()->board()->getFieldsAndCrops();
    $groups = [];

    foreach ($zones as $z) {
      $key = $z['id'] ?? $z['uid'] ?? null;
      if ($key === null) continue;

      if (!isset($groups[$key])) {
        $groups[$key] = [
          'zones'   => [],
          'crops'   => [],
          'repZone' => null,
        ];
      }

      $groups[$key]['zones'][] = $z;

      if (!empty($z['crops'])) {
        $groups[$key]['crops'] = array_merge($groups[$key]['crops'], $z['crops']);
      }

      if (($z['type'] ?? null) === 'fieldCard') {
        $groups[$key]['repZone'] = $z;
      } elseif ($groups[$key]['repZone'] === null) {
        $groups[$key]['repZone'] = $z;
      }
    }

    return $groups;
  }

  private function uidsForAnimation(array $group, array $sel): array
  {
    $uids = [];
    foreach ($group['zones'] as $z) {
      if (!empty($z['uid'])) $uids[] = $z['uid'];
    }
    if (empty($uids) && !empty($sel['uid'])) $uids[] = $sel['uid'];
    return array_values(array_unique($uids));
  }
}
