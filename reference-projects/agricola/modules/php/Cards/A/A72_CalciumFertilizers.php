<?php
namespace AGR\Cards\A;
use AGR\Core\Notifications;
use AGR\Managers\Meeples;
use AGR\Managers\Players;

class A72_CalciumFertilizers extends \AGR\Models\MinorImprovement
{
  public function __construct($row)
  {
    parent::__construct($row);
    $this->id = 'A72_CalciumFertilizers';
    $this->name = clienttranslate('Calcium Fertilizers');
    $this->deck = 'A';
    $this->number = 72;
    $this->category = CROP_PROVIDER;
    $this->desc = [
      clienttranslate(
        'Each time you use a __Quarry__ accumulation space, add 1 additional good of the respective type to each of your planted fields growing a single type of crop.'
      ),
    ];
    $this->prerequisite = clienttranslate('No Field Tiles');
    $this->isArtifexOrBubulcus = true;
  }

  public function isBuyable($player, $ignoreResources = false, $args = [])
  {
    if (count($player->board()->getFieldTiles()) > 0) {
      return false;
    }

    return parent::isBuyable($player, $ignoreResources, $args);
  }

  public function isListeningTo($event)
  {
    return $this->isActionCardEvent($event, 'EasternQuarry') ||
      $this->isActionCardEvent($event, 'WesternQuarry');
  }

  public function onPlayerPlaceFarmer($player, $event)
  {
    $valid = $this->getValidFieldGroups();
    if (empty($valid)) {
      return;
    }

    return [
      'action' => SPECIAL_EFFECT,
      'countAsUse' => true,
      'args' => [
        'cardId' => $this->id,
        'method' => 'plantAdditionalGoods',
        'args' => [],
      ],
    ];
  }

  private function getValidFieldGroups(): array
  {
    $player = $this->getPlayer();
    $planted = $player->board()->getPlantedFields();
    if (empty($planted)) {
      return [];
    }

    $groups = $player->board()->groupFields($planted);

    $valid = [];

    foreach ($groups as $groupSlots) {
      $types = [];
      $repField = null;

      foreach ($groupSlots as $field) {
        $repField = $repField ?? $field;

        foreach (($field['crops'] ?? []) as $crop) {
          if (isset($crop['type'])) {
            $types[] = $crop['type'];
          }
        }
      }

      if (empty($types)) {
        continue;
      }

      $unique = array_values(array_unique($types));
      if (count($unique) !== 1) {
        continue;
      }

      $type = $unique[0];

      if (!in_array($type, CROPS, true)) {
        continue;
      }

      $valid[] = [
        'field' => $repField,
        'type' => $type,
      ];
    }

    return $valid;
  }

  public function getPlantAdditionalGoodsDescription()
  {
    return clienttranslate('Gain additional goods in your fields');
  }

  public function plantAdditionalGoods()
  {
    $player = Players::getActive();
    $valid = $this->getValidFieldGroups();
    if (empty($valid)) {
      return;
    }

    $sows = [];

    foreach ($valid as $entry) {
      $field = $entry['field'];
      $type = $entry['type'];

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
        'type' => $type,
        'crops' => Meeples::getMany($ids)->toArray(),
      ];
    }

    Notifications::sow($player, $sows, true);
    Notifications::updateDropZones($player);
  }
}
