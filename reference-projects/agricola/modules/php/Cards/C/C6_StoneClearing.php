<?php

namespace AGR\Cards\C;

use AGR\Managers\Meeples;
use AGR\Core\Notifications;
use AGR\Models\MinorImprovement;

class C6_StoneClearing extends MinorImprovement
{
  public function __construct($row)
  {
    parent::__construct($row);
    $this->id = 'C6_StoneClearing';
    $this->name = clienttranslate('Stone Clearing');
    $this->deck = 'C';
    $this->number = 6;
    $this->category = BUILDING_RESOURCE_PROVIDER;
    $this->desc = [
      clienttranslate(
        'Immediately place 1 <STONE> on each of your empty fields. Harvest them during the next field phase. These fields are considered planted until then.'
      ),
    ];
    $this->passing = true;
    $this->cost = [
      FOOD => 1,
    ];
  }

  public function onBuy($player)
  {
    // Empty sowable slots (includes Rock Garden / Wood Field slots)
    $sowable = $player->board()->getSowableFields(null, true);
    if (empty($sowable)) {
      return;
    }

    $planted = $player->board()->getPlantedFields();

    // Group both lists into logical fields (multi-slot cards collapse to 1 group)
    $sowableGroups = $player->board()->groupFields($sowable);
    $plantedGroups = $player->board()->groupFields($planted);

    $sows = [];

    foreach ($sowableGroups as $groupId => $groupSlots) {
      // If ANY slot in this logical field has crops, it's not an "empty field"
      if (!empty($plantedGroups[$groupId])) {
        continue;
      }

      // Place exactly 1 stone per logical field.
      // Use the first slot in the group as the target.
      $field = $groupSlots[0];

      $location = ($field['type'] === 'fieldCard') ? $field['id'] : 'board';

      $ids = Meeples::createResourceInLocation(
        STONE,
        $location,
        $field['pId'],
        $field['x'],
        $field['y'],
        1
      );

      $sows[] = [
        'field' => $field,
        'seed' => null,
        'crops' => Meeples::getMany($ids)->toArray(),
      ];
    }

    if (!empty($sows)) {
      Notifications::sow($player, $sows, true);
    }
  }
}
