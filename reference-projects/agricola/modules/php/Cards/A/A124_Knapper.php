<?php
namespace AGR\Cards\A;
use AGR\Managers\ActionCards;

class A124_Knapper extends \AGR\Models\Occupation
{
  public function __construct($row)
  {
    parent::__construct($row);
    $this->id = 'A124_Knapper';
    $this->name = clienttranslate('Knapper');
    $this->deck = 'A';
    $this->number = 124;
    $this->category = BUILDING_RESOURCE_PROVIDER;
    $this->desc = [
      clienttranslate('Each time before you use an action space card on round spaces 5 to 7, you get 1 <STONE>.'),
    ];
    $this->players = '1+';
    $this->isArtifexOrBubulcus = true;
  }
  
  public function isListeningTo($event)
  {
    return $this->isActionCardTurnEvent($event, [5, 6, 7]);
  }

  public function onPlayerPlaceFarmer($player, $args)
  {
    return $this->gainNode([STONE => 1]);
  }

  public function onPlayerComputeArgsPlaceFarmer($player) 
  {
    $added = [];

    $cards = ActionCards::getVisible($player)
    ->filter(function ($space) {
      return $space->getTurn() >= 5 && $space->getTurn() <= 7;
    });

    foreach ($cards as $card) {
      $added[] = ['actionCardId' => $card->getId(), 'ignoreResources' => true];
    }

    return $added;
  }
}
