<?php
namespace AGR\Cards\A;
use AGR\Helpers\Utils;
use AGR\Managers\ActionCards;

class A126_MasterWorkman extends \AGR\Models\Occupation
{
  public function __construct($row)
  {
    parent::__construct($row);
    $this->id = 'A126_MasterWorkman';
    $this->name = clienttranslate('Master Workman');
    $this->deck = 'A';
    $this->number = 126;
    $this->category = BUILDING_RESOURCE_PROVIDER;
    $this->desc = [
      clienttranslate(
        'Each time before you use an action space card on round spaces 1/2/3/4, you get 1 <WOOD>/<CLAY>/<REED>/<STONE>.'
      ),
    ];
    $this->players = '1+';
    $this->isArtifexOrBubulcus = true;
  }

  public function isListeningTo($event)
  {
    return $this->isActionCardTurnEvent($event, [1, 2, 3, 4]);      
  }

  public function onPlayerPlaceFarmer($player, $event)
  {
    $resource = [WOOD, CLAY, REED, STONE];
    $cardId = $event['actionCardId'];
    $turn = Utils::getActionCard($cardId)->getTurn();
    
    return $this->gainNode([$resource[$turn-1] => 1]);
  }

  public function onPlayerComputeArgsPlaceFarmer($player) 
  {
    $added = [];

    $cards = ActionCards::getVisible($player)
    ->filter(function ($space) {
      return $space->getTurn() >= 1 && $space->getTurn() <= 4;
    });

    foreach ($cards as $card) {
      $added[] = ['actionCardId' => $card->getId(), 'ignoreResources' => true];
    }

    return $added;
  }
}
