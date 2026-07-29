<?php
namespace AGR\Cards\B;
use AGR\Managers\Farmers;
use AGR\Managers\Players;
use AGR\Core\Stats;
use AGR\Managers\ActionCards;

class B129_Seatmate extends \AGR\Models\Occupation
{
  public function __construct($row)
  {
    parent::__construct($row);
    $this->id = 'B129_Seatmate';
    $this->name = clienttranslate('Seatmate');
    $this->deck = 'B';
    $this->number = 129;
    $this->category = ACTIONS_BOOSTER;
    $this->desc = [
      clienttranslate(
        'You can use the action space on round space 13 even if it is occupied by one or more people of the players to your immediate left and right.'
      ),
    ];
    $this->players = '3+';
    $this->isArtifexOrBubulcus = true;
  }

  public function onPlayerComputeArgsPlaceFarmer($player)
  {
    $r13 = ActionCards::getVisible($player)
      ->filter(function ($card) {
        return $card->getTurn() == 13;
      })
      ->getIds();
    if(count($r13)==0){
      return;
    }
    $added = [];
    if(Players::count() == 3){
      $added[] = ['actionCardId' => $r13[0], 'playerConstraint' => $player->getId()];
      return $added;
    }
    $pos = Stats::getPosition($player);
    $opposition = 0;
    //TODO: change for 56 expansion
    if($pos==1){
      $opposition = 3;
    }
    if($pos==2){
      $opposition = 4;
    }
    if($pos==3){
      $opposition = 1;
    }
    if($pos==4){
      $opposition = 2;
    }
    foreach (Players::getAll() as $player2) {
      if(Stats::getPosition($player2)!=$opposition){
        continue;
      }
      if(!Farmers::getOnCard($r13[0], $player2->getId())->empty()){
        return;
      }
    }
    $added[] = ['actionCardId' => $r13[0], 'playerConstraint' => $player->getId()];
    return $added;
  }
}
