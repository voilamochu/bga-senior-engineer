<?php

namespace AGR\Cards\B;

use AGR\Helpers\Utils;
use AGR\Models\MinorImprovement;

class B56_Brook extends MinorImprovement
{
  public function __construct($row)
  {
    parent::__construct($row);
    $this->id = 'B56_Brook';
    $this->name = clienttranslate('Brook');
    $this->deck = 'B';
    $this->number = 56;
    $this->category = FOOD_PROVIDER;
    $this->desc = [
      clienttranslate(
        'Each time you use one of the four action spaces above the __Fishing__ accumulation space, you get 1 additional <FOOD>.'
      ),
    ];
    $this->costText = clienttranslate('1 of Your People on Fishing');
  }

  public function isBuyable($player, $ignoreResources = false, $args = [])
  {
    $isFishingEmpty = $player
      ->getAllFarmers()
      ->filter(function ($farmer) {
        return $farmer['location'] == 'ActionFishing';
      })
      ->empty();

    return !$isFishingEmpty && parent::isBuyable($player, $ignoreResources, $args);
  }

  public function isListeningTo($event)
  {
    $turn1 = false;
    $cardId = $event['actionCardId'] ?? null;
    if ($cardId != null) {
      if (Utils::getActionCard($cardId)->getTurn() == 1) {
        $turn1 = $this->isActionCardEvent($event, substr($cardId, 6));
      }
    }
    return $turn1 ||
      $this->isActionCardEvent($event, 'ReedBank') ||
      $this->isActionCardEvent($event, 'ClayPit') ||
      $this->isActionCardEvent($event, 'Forest');
  }

  public function onPlayerPlaceFarmer($player, $args)
  {
    return $this->gainNode([FOOD => 1]);
  }
}
