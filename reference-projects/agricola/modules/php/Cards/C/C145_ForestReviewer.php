<?php
namespace AGR\Cards\C;
use AGR\Managers\Farmers;

class C145_ForestReviewer extends \AGR\Models\Occupation
{
  public function __construct($row)
  {
    parent::__construct($row);
    $this->id = 'C145_ForestReviewer';
    $this->name = clienttranslate('Forest Reviewer');
    $this->deck = 'C';
    $this->number = 145;
    $this->category = ACTIONS_BOOSTER;
    $this->desc = [
      clienttranslate(
        'Each time after any player (including you) uses the unoccupied __Grove__ or __Forest__ accumulation space while the other of the two is occupied, you get 1 <REED>.'
      ),
    ];
    $this->players = '3+';
    $this->isCorbariusOrDulcinaria = true;
  }
  
  public function isListeningTo($event)
  {
    if (!$this->isActionEvent($event, 'PlaceFarmer', null)) {
      return false;
    }

    if ($event['actionCardType'] != 'Grove' && $event['actionCardType'] != 'Forest') {
      return false;
    }

    // Occupancy must be judged when the space is taken, before reactions (e.g. an extra placement) resolve
    $action = $event['actionCardType'] == 'Grove' ? 'ActionGrove' : 'ActionForest';
    $other = $event['actionCardType'] == 'Grove' ? 'ActionForest' : 'ActionGrove';
    return !Farmers::getOnCard($other)->empty() &&
      Farmers::getOnCard($action)->count() == 1;
  }

  public function onAfterPlaceFarmer($player, $event)
  {
    return $this->gainNode([REED => 1]);
  }

  public function onPlayerAfterPlaceFarmer($player, $event)
  {
    return $this->onAfterPlaceFarmer($player, $event);
  }  
  
  public function onOpponentAfterPlaceFarmer($player, $event)
  {
    return $this->onAfterPlaceFarmer($player, $event);
  }
}
