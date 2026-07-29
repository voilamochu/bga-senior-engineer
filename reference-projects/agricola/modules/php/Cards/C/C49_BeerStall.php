<?php

namespace AGR\Cards\C;

use AGR\Helpers\Utils;
use AGR\Managers\Farmers;
use AGR\Models\MinorImprovement;

class C49_BeerStall extends MinorImprovement
{
  public function __construct($row)
  {
    parent::__construct($row);
    $this->id = 'C49_BeerStall';
    $this->name = clienttranslate('Beer Stall');
    $this->deck = 'C';
    $this->number = 49;
    $this->category = FOOD_PROVIDER;
    $this->desc = [
      clienttranslate(
        'In the feeding phase of each harvest, for each empty unfenced stable you have, you can exchange 1 <GRAIN> for 5 <FOOD>.'
      ),
    ];
    $this->cost = [
      WOOD => '1',
    ];
    $this->isCorbariusOrDulcinaria = true;
  }

  public function isListeningTo($event)
  {
    return $this->isPlayerEvent($event) && $event['type'] == 'HarvestFeedingPhase';
  }

  public function onPlayerHarvestFeedingPhase($player, $event)
  {
    $n = $this->getEmptyUnfencedStables($player);
    $grains = $player->countReserveResource(GRAIN);
    $childs = [];
    for ($i = 1; $i <= $n; $i++) {
      if ($i <= $grains) {
        $childs[] = $this->payGainNode([GRAIN => $i], [FOOD => 5 * $i], null, false);
      }
    }
    if ($n > 0) {
      return [
        'type' => NODE_XOR,
        'optional' => true,
        'countAsUse' => true,
        'doableRequiresChild' => true,
        'customDescription' => 'Pay <GRAIN>',
        'childs' => $childs,
      ];
    }
  }

  public function getEmptyUnfencedStables($player)
  {
    $emptyUnfencedStables = 0;
    $zonesAnimals = $player->board()->getAnimalsDropZonesWithAnimals();

    foreach ($zonesAnimals as $zone) {
      if ($zone['type'] == 'stable' && $zone['animals'] == 0) {
        $emptyUnfencedStables = $emptyUnfencedStables + 1;
      }
    }

    if ($player->board()->hasFarmHandStable()) {
      $pId = $player->getId();
      $farmers = Farmers::count($pId);
      $capacityWithoutFarmHandStable = $player->countRooms() + $player->countExtraRoom() - 1;
      $isFarmHandStableOccupiedByFarmer = ($farmers > $capacityWithoutFarmHandStable);

      if (!$isFarmHandStableOccupiedByFarmer) {
      $emptyUnfencedStables = $emptyUnfencedStables + 1;
      }
    }

    return $emptyUnfencedStables;
  }
}
