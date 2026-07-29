<?php
namespace AGR\Cards\B;
use AGR\Core\Notifications;
use AGR\Helpers\CardRulings;
use AGR\Managers\Meeples;


class B115_TinsmithMaster extends \AGR\Models\Occupation
{
  public function __construct($row)
  {
    parent::__construct($row);
    $this->id = 'B115_TinsmithMaster';
    $this->name = clienttranslate('Tinsmith Master');
    $this->deck = 'B';
    $this->number = 115;
    $this->category = CROP_PROVIDER;
    $this->desc = [
      clienttranslate(
        'You can hold 1 additional animal in each pasture without a stable. Each time you sow in a field, you can place 1 additional crop of the respective type in that field.'
      ),
    ];
    $this->players = '1+';
    $this->isArtifexOrBubulcus = true;

    $this->rulings = array_merge(
      CardRulings::fromKeys([
      'WOOD_STONE_NOT_CROPS',
      ]),
      [
      clienttranslate('This effect places one extra crop on top of the usual stack, not a second stack in each field.'),
      clienttranslate('This does not add a condition to sowing.'),
      ]
    );

  }

  protected function onBuy($player)
  {
    $this->refreshDropZones();
  }

  public function isListeningTo($event)
  {
    return $this->isActionEvent($event, 'Sow');
  }

  public function onPlayerAfterSow($player, $event)
  {
    $this->setEligibleZonesFromEvent($event);
    $zones = $this->getExtraDatas('zones') ?? [];

    if (empty($zones)) {
      return null;
    }

    if (count($zones) == 1) {
      $zoneTypes = $this->getExtraDatas('zoneTypes') ?? [];
      $cropType = array_values($zoneTypes)[0];
      return [
        'action' => SPECIAL_EFFECT,
        'optional' => true,
        'args' => [
          'cardId' => $this->id,
          'method' => 'addSingleGood',
          'args' => [$zones, $cropType],
        ],
      ];
    }

    return [
      'action' => SPECIAL_EFFECT,
      'optional' => true,
      'args' => [
        'cardId' => $this->id,
        'method' => 'addAdditionalGood',
        'args' => [$zones],
      ],
    ];
  }

  public function getAddSingleGoodDescription($zones, $cropType)
  {
    $icon = '<' . strtoupper($cropType) . '>';
    return [
      'log' => clienttranslate('Add additional ${resource}'),
      'args' => [
        'resource' => $icon,
      ],
    ];
  }

  public function addSingleGood($zones, $cropType)
  {
    $this->actAddAdditionalGood($zones);
  }

  private function setEligibleZonesFromEvent($event)
  {
    $zones = [];
    $zoneTypes = [];

    foreach (($event['sows'] ?? []) as $sow) {
      $field = $sow['field'] ?? null;
      if ($field === null) {
        continue;
      }

      $type = $sow['type'] ?? null;
      if (!in_array($type, CROPS, true)) {
        continue;
      }

      $uid = $field['uid'] ?? ($field['x'] . '_' . $field['y']);

      if (isset($zoneTypes[$uid])) {
        continue;
      }

      $zones[] = $field;
      $zoneTypes[$uid] = $type;
    }

    $this->setExtraDatas('zones', $zones);
    $this->setExtraDatas('zoneTypes', $zoneTypes);
  }

  public function getAddAdditionalGoodDescription()
  {
    return clienttranslate('Choose fields to put additional crops in');
  }

  public function argsAddAdditionalGood()
  {
    return [
      'cardId' => $this->id,
      'description' => clienttranslate('${actplayer} may choose fields to put additional crops in (Tinsmith Master)'),
      'descriptionmyturn' => clienttranslate('${you} may choose fields to put additional crops in (Tinsmith Master)'),
      'zones' => $this->getExtraDatas('zones'),
    ];
  }

  public function actAddAdditionalGood($zones)
  {
    $player = $this->getPlayer();
    $zoneTypes = $this->getExtraDatas('zoneTypes') ?? [];
    $sows = [];

    foreach (($zones ?? []) as $field) {
      $uid = $field['uid'] ?? ($field['x'] . '_' . $field['y']);
      $type = $zoneTypes[$uid] ?? null;

      if (!in_array($type, CROPS, true)) {
        continue;
      }

      $location = (($field['type'] ?? null) === 'fieldCard') ? $field['id'] : 'board';

      $ids = Meeples::createResourceInLocation(
        $type,
        $location,
        $field['pId'],
        $field['x'],
        $field['y'],
        1
      );

      $sows[] = [
        'field' => $field,
        'crops' => Meeples::getMany($ids)->toArray(),
      ];
    }

    if (empty($sows)) {
      return;
    }

    Notifications::sow($player, $sows, true);
  }

  public function onPlayerComputeDropZones($player, &$args)
  {
    foreach ($args['zones'] as &$zone) {
      if ($zone['type'] == 'pasture') {
        if (count($zone['stables']) == 0) {
          $zone['capacity'] += 1;
        }
      }
    }
  }
}
