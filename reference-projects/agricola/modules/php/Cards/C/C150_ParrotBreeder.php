<?php
namespace AGR\Cards\C;
use AGR\Core\Globals;
use AGR\Managers\Farmers;
use AGR\Managers\Players;
use AGR\Core\Stats;
use AGR\Managers\ActionCards;

class C150_ParrotBreeder extends \AGR\Models\Occupation
{
  public function __construct($row)
  {
    parent::__construct($row);
    $this->id = 'C150_ParrotBreeder';
    $this->name = clienttranslate('Parrot Breeder');
    $this->deck = 'C';
    $this->number = 150;
    $this->category = ACTIONS_BOOSTER;
    $this->desc = [
      clienttranslate(
        'On your turn, if you pay 1 <GRAIN> to the general supply, you can use the same action space (unless it is the __Meeting Place__ action space) that the player to your right has just used on their turn (not retroactive).'
      ),
    ];
    $this->players = '4+';
    $this->isCorbariusOrDulcinaria = true;
  }

  public function isListeningTo($event)
  {
    return $this->isActionEvent($event, 'PlaceFarmer', null) || (Globals::isWorkPhase() && $this->isAnytime($event) && !$this->isFlagged());
  }
  
  public function onPlayerAfterPlaceFarmer($player, $event)
  {
    $this->setExtraDatas('right',null);
    return $this->unflagCardNode();
  }  
  
  public function onOpponentAfterPlaceFarmer($player2, $event)
  {
    $player = $this->getPlayer();
    $pos = Stats::getPosition($player);
    $opposition = $pos - 1;

    if($opposition == 0){
      $opposition = Players::count();
    }
    //print($pos);
    //print($opposition);
    //print(Stats::getPosition($player2));
    if(Stats::getPosition($player2) != $opposition){
      $this->setExtraDatas('right',null);
    }
    else{
      $this->setExtraDatas('right',$event['actionCardId']);
    }
    return $this->unflagCardNode();
  } 
 
  public function onPlayerAtAnytime($player, $event)
  {
    return  [
      'type' => NODE_SEQ,
      'optional' => false,
      'childs' => [
        $this->flagCardNode(),
        $this->payNode([GRAIN => 1]), 
      ]
    ];
  }

  public function onPlayerComputeArgsPlaceFarmer($player)
  {
    if(!$this->isFlagged() || $this->getExtraDatas('right')==null){
      return;
    }
    $added = [];
    if (in_array($this->getExtraDatas('right'),['ActionMeetingPlace', 'ActionMeetingPlaceSolo'])) {
        return;
    }
    $added[] = ['actionCardId' => $this->getExtraDatas('right'), 'playerConstraint' => 'dummy'];
    return $added;
  }
}
