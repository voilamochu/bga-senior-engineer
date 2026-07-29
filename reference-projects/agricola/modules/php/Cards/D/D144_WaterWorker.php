<?php

namespace AGR\Cards\D;

use AGR\Helpers\Utils;
use AGR\Models\Occupation;

class D144_WaterWorker extends Occupation
{
  public function __construct($row)
  {
    parent::__construct($row);
    $this->id = 'D144_WaterWorker';
    $this->name = clienttranslate('Water Worker');
    $this->deck = 'D';
    $this->number = 144;
    $this->isCorbariusOrDulcinaria = true;
    $this->category = BUILDING_RESOURCE_PROVIDER;
    $this->desc = [
      clienttranslate(
        'Each time after you use any of the __Fishing__, __Reed Bank__, __Day Laborer__ spaces or the action space of round 4, you get 1 additional <REED>.'
      ),
    ];
    $this->players = '3+';
  }

  public function isListeningTo($event)
  {
    // Fishing (to sync with A95_Angler and D73_SupplyBoat
    if ($this->isCollectEvent($event, FOOD) && $event['actionCardId'] == 'ActionFishing') {
      return true;
    }

    // DayLaborer, ReedBank and Turn 4
    if (!$this->isActionEvent($event, 'PlaceFarmer')) {
      return false;
    }

    $turn4 = false;
    $cardId = $event['actionCardId'] ?? null;
    if ($cardId != null) {
      $turn = Utils::getActionCard($cardId)->getTurn();
      if ($turn == 4) {
        $turn4 = $event['actionCardType'] == substr($cardId, 6);
      }
    }
    return $turn4 ||
      $event['actionCardType'] == 'DayLaborer' ||
      $event['actionCardType'] == 'ReedBank';
  }

  public function onPlayerAfterCollect($player, $args)
  {
    return $this->gainNode([REED => 1]);
  }

  public function onPlayerAfterPlaceFarmer($player, $args)
  {
    return $this->gainNode([REED => 1]);
  }
}
