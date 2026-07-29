<?php
namespace AGR\Cards\C;
use AGR\Core\Globals;
use AGR\Managers\ActionCards;
use AGR\Managers\Farmers;

class C117_Legworker extends \AGR\Models\Occupation
{
  public function __construct($row)
  {
    parent::__construct($row);
    $this->id = 'C117_Legworker';
    $this->name = clienttranslate('Legworker');
    $this->deck = 'C';
    $this->number = 117;
    $this->isCorbariusOrDulcinaria = true;
    $this->category = BUILDING_RESOURCE_PROVIDER;
    $this->desc = [
      clienttranslate(
        'Each time you use an action space that is orthogonally adjacent to another action space occupied by one of your people, you get 1 <WOOD>.'
      ),
    ];
    $this->players = '1+';
  }

  public function isListeningTo($event)
  {
    return $this->isActionCardEvent($event, null);      
  }

  public function onPlayerPlaceFarmer($player, $event)
  {
    //The Job Contract fake farmer should not give wood for being adjacent to Day Laborer, but should if adjacent to any other spaces occupied by this player.  
    $cardId = $event['actionCardId'];
    $excludeSpace = null;

    if (($event['farmerPlaced'] ?? true) === false) {
      if (Globals::getJobContractFake() === null) {
        return;
      }
      $excludeSpace = 'ActionDayLaborer';
    }

    if ($this->hasAdjacentWorker($player, $cardId, $excludeSpace)) {
      return $this->gainNode([WOOD => 1]);
    }
  }

  public function hasAdjacentWorker($player, $actionCardId, $excludeSpace = null)
  {
    $hasAdjacent = false;
    $card = ActionCards::get($actionCardId);
    if ($card->getActionCardType() == 'special') {
      return false;
    }

    $adjacentSpaces = ActionCards::get($actionCardId)->getAdjacentSpaces();

    foreach ($adjacentSpaces as $space) {
      if (is_int($space)) {
        $space = ActionCards::getInLocation(['turn', $space])->first()->getId();
      }

      if ($space === $excludeSpace) {
        continue;
      }

      if (!Farmers::getOnCard($space, $player->getId())->empty()) {
        if ($space == '') {
          continue;
        }
        $hasAdjacent = true;
        break;
      }
    }

    return $hasAdjacent;
  }

  public function onPlayerComputeArgsPlaceFarmer($player) 
  {
    $added = [];

    $cards = ActionCards::getVisible($player);
    foreach ($cards as $card) {
      $actionCardId = $card->getId();
      if ($this->hasAdjacentWorker($player, $actionCardId)) {
        $added[] = ['actionCardId' => $actionCardId, 'ignoreResources' => true];
      }
    }

    return $added;
  }
}
