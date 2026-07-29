<?php
namespace AGR\Cards\E;

use AGR\Core\Globals;

class E93_Motivator extends \AGR\Models\Occupation
{
  public function __construct($row)
  {
    parent::__construct($row);
    $this->id = 'E93_Motivator';
    $this->name = clienttranslate('Motivator');
    $this->deck = 'E';
    $this->author = 'chris';
    $this->number = 93;
    $this->category = 'ACTION_-_GUEST';
    $this->desc = [
      clienttranslate(
        'On your first turn each round, if you have no unused farmyard spaces, you can place a person from your supply.'
      ),
    ];
    $this->players = '1+';
  }

  public function canBeActivated()
  {
    if ($this->isFlagged() || count($this->getPlayer()->board()->getFreeZones()) > 0) {
      return false;
    }

    return true;
  }

  public function isListeningTo($event)
  {
    return ($this->isPlayerEvent($event) && $event['type'] == 'StartOfTurn') ||
      ($this->isActionEvent($event, 'PlaceFarmer') && !$this->isFlagged());
  }

  public function onPlayerStartOfTurn($player, $event)
  {
    return $this->unflagCardNode();
  }

  public function onPlayerAfterPlaceFarmer($player, $args)
  {
    if (!Globals::isWorkPhase()) {
      return;
    }
    return $this->flagCardNode();
  }

}
