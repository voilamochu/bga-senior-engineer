<?php
namespace AGR\Cards\C;
use AGR\Core\Globals;
use AGR\Managers\ActionCards;
use AGR\Core\Notifications;

class C21_HeartofStone extends \AGR\Models\MinorImprovement
{
  public function __construct($row)
  {
    parent::__construct($row);
    $this->id = 'C21_HeartofStone';
    $this->name = clienttranslate('Heart of Stone');
    $this->deck = 'C';
    $this->number = 21;
    $this->category = ACTIONS_BOOSTER;
    $this->desc = [
      clienttranslate(
        'Each time a __Quarry__ accumulation space is revealed, if you have room in your house, you can immediately take a __Family Growth__ action without placing a person.'
      ),
    ];
    $this->cost = [
      FOOD => 4,
    ];
    $this->holder = true;
    $this->isCorbariusOrDulcinaria = true;
    $this->bannedWeak1or2p = true;
  }

  public function isListeningTo($event)
  {
    return $this->isPlayerEvent($event) && 
      $event['type'] == 'AfterRevealAction' &&
      (Globals::getLastRevealed() == 'ActionWesternQuarry' || 
       Globals::getLastRevealed() == 'ActionEasternQuarry');
  }

  public function onPlayerAfterRevealAction($player, $event)
  {
    return [
      'action' => WISHCHILDREN,
      'cardId' => $this->id,
      'optional' => true,
      'args' => ['constraints' => ['freeRoom'], 'cardLocation' => $this->id],
      'source' => $this->name
    ];
  }
} 
