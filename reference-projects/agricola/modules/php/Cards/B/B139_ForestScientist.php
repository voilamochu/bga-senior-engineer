<?php
namespace AGR\Cards\B;
use AGR\Managers\ActionCards;
use AGR\Managers\Meeples;
use AGR\Core\Globals;

class B139_ForestScientist extends \AGR\Models\Occupation
{
  public function __construct($row)
  {
    parent::__construct($row);
    $this->id = 'B139_ForestScientist';
    $this->name = clienttranslate('Forest Scientist');
    $this->deck = 'B';
    $this->number = 139;
    $this->category = FOOD_PROVIDER;
    $this->desc = [
      clienttranslate(
        'In the returning home phase of each round, if there is no wood left on the game board, you get 1 <FOOD>—from round 5 on, even 2 <FOOD>.'
      ),
    ];
    $this->players = '3+';
    $this->isArtifexOrBubulcus = true;
    $this->bannedWeak3or4p = true;
  }

    public function isListeningTo($event)
  {
    return $this->isPlayerEvent($event) && 
      $event['type'] == 'ReturnHome';
  }

  public function onPlayerReturnHome($player, $event)
  {
    $cards = ActionCards::getVisible($player);
    $wood = 0;

    foreach ($cards as $card) {
      $wood += Meeples::getResourcesOnCard($card->id, null, WOOD)->count();
      if ($wood > 0) {
        return;
      }
    }
    
    $n = Globals::getTurn() >= 5 ? 2 : 1;
    return $this->gainNode([FOOD => $n]);
  }
}
