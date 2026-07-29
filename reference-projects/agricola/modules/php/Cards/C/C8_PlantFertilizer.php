<?php
namespace AGR\Cards\C;

use AGR\Core\Notifications;
use AGR\Managers\Meeples;
use AGR\Managers\Players;

class C8_PlantFertilizer extends \AGR\Models\MinorImprovement
{
  public function __construct($row)
  {
    parent::__construct($row);
    $this->id = 'C8_PlantFertilizer';
    $this->name = clienttranslate('Plant Fertilizer');
    $this->deck = 'C';
    $this->number = 8;
    $this->category = CROP_PROVIDER;
    $this->desc = [
      clienttranslate('In each field with exactly 1 good, you can immediately place 1 additional good of the same type.'),
    ];
    $this->passing = true;
    $this->isCorbariusOrDulcinaria = true;

    $this->rulings = [
      clienttranslate('<PIG> on unplanted fields from __Mud Patch__ (A011) do not apply for this effect.'),
    ];
  }

  /**
   * Return all grouped fields that currently have exactly 1 good in total.
   *
   * Each entry is ['field' => <stack array>, 'type' => <resource type>].
   */
  private function getEligibleFields($player): array
  {
    $fields = $player->board()->getPlantedFields();
    if (empty($fields)) {
      return [];
    }

    // Group by logical field id (Wood Field / Rock Garden treated as one logical field)
    $groups = $player->board()->groupFields($fields);

    $eligible = [];

    foreach ($groups as $group) {
      $totalGoods = 0;
      $targetField = null;
      $goodType = null;

      foreach ($group as $field) {
        $crops = $field['crops'] ?? [];
        $count = count($crops);
        if ($count > 0) {
          $totalGoods += $count;
          if ($count === 1 && $targetField === null) {
            // Remember the exact stack and type of the single good
            $targetField = $field;
            $goodType = $crops[0]['type']; // GRAIN / VEGETABLE / WOOD / STONE
          }
        }
      }

      // Grouped field qualifies only if it has exactly 1 good in total
      if ($totalGoods === 1 && $targetField !== null && $goodType !== null) {
        $eligible[] = [
          'field' => $targetField,
          'type' => $goodType,
        ];
      }
    }

    return $eligible;
  }

  public function onBuy($player)
  {
    $eligibleFields = $this->getEligibleFields($player);
    if (empty($eligibleFields)) {
      return;
    }

    return [
      'type' => NODE_SEQ,
      'optional' => true,
      'childs' => [
        [
          'action' => SPECIAL_EFFECT,
          'optional' => true,
          'args' => [
            'cardId' => $this->id,
            'method' => 'plantAdditionalGoods',
            'args' => [$eligibleFields],
          ],
        ],
      ],
    ];
  }

  public function getPlantAdditionalGoodsDescription()
  {
    return clienttranslate('Gain additional goods in your fields');
  }

  //Apply the extra good to each eligible field.
  
  public function plantAdditionalGoods($eligibleFields)
  {
    $player = Players::getActive();

    if (empty($eligibleFields)) {
      return;
    }

    $sows = [];

    foreach ($eligibleFields as $entry) {
      $field = $entry['field'];
      $type = $entry['type'];

      $location = ($field['type'] === 'fieldCard') ? $field['id'] : 'board';

      $ids = Meeples::createResourceInLocation(
        $type,
        $location,
        $field['pId'],
        $field['x'],
        $field['y'],
        1
      );

      $sows[] = [
        // For multi-slot cards we keep using the existing id/uid pattern
        'field' => $field['id'] ?? $field['uid'] ?? null,
        'type' => $type,
        'seed' => null,
        'crops' => Meeples::getMany($ids)->toArray(),
      ];
    }

    Notifications::sow($player, $sows, true);
    Notifications::updateDropZones($player);
  }
}
