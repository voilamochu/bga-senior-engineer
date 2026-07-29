<?php
namespace AGR\Cards\A;
use AGR\Managers\Farmers;
use AGR\Core\Globals;

class A130_MummysBoy extends \AGR\Models\Occupation
{
  public function __construct($row)
  {
    parent::__construct($row);
    $this->id = 'A130_MummysBoy';
    $this->name = clienttranslate("Mummy's Boy");
    $this->deck = 'A';
    $this->number = 130;
    $this->category = ACTIONS_BOOSTER;
    $this->desc = [
      clienttranslate(
        'Once per round, when placing a person after your first two, you can place it on the action space with your 2nd person and use that space again, unless it is on the __Meeting Place__ action space.'
      ),
    ];
    $this->players = '3+';
    $this->isArtifexOrBubulcus = true;
  }

  public function isListeningTo($event)
  {
     return (!$this->isFlagged() && $this->isActionEvent($event,'PlaceFarmer')) || ($this->isPlayerEvent($event) && $event['type'] == 'StartOfTurn');
  }

  public function onPlayerStartOfTurn($player, $event)
  {
    return $this->unflagCardNode();
  }
  
  public function onPlayerAfterPlaceFarmer($player, $event)
  {
    if ($player->countPlacedFarmers() < 3 || $this->isFlagged()) {
      return;
    }
    $cId = $event['actionCardId'];
    $second = Globals::getPlacedFarmers()[$player->getId()][1];
    $loc = Farmers::getMany($second)->first()['location'];
    if(count(Farmers::getOnCard($cId))>1 && $player->countPlacedFarmers() >= 3 && $cId == $loc){
      return $this->flagCardNode();
    }
  }
  
  public function onPlayerComputeArgsPlaceFarmer($player)
  {
    if ($player->countPlacedFarmers() < 2 || $this->isFlagged()) {
      return;
    }
    $added = [];
    $second = Globals::getPlacedFarmers()[$player->getId()][1];
    $loc = Farmers::getMany($second)->first()['location'];
    if (in_array($loc,['ActionMeetingPlace', 'ActionMeetingPlaceSolo'])) {
        return;
    }
    $added[] = ['actionCardId' => $loc, 'playerConstraint' => 'dummy'];
    return $added;
  }
}
