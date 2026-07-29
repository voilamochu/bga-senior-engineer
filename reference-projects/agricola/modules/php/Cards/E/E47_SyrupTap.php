<?php
namespace AGR\Cards\E;
use AGR\Managers\Players;

class E47_SyrupTap extends \AGR\Models\MinorImprovement
{
  public function __construct($row)
  {
    parent::__construct($row);
    $this->id = 'E47_SyrupTap';
    $this->name = clienttranslate('Syrup Tap');
    $this->deck = 'E';
    $this->author = 'kulbrot';
    $this->number = 47;
    $this->category = FOOD;
    $this->desc = [
      clienttranslate(
        'Each time you get at least 1 <WOOD> from an action space, place 1 <FOOD> on the next round space. At the start of that round, you get the <FOOD>.'
      ),
    ];
    $this->vp = 1;
    $this->cost = [WOOD => 1, STONE => 1];
  }

  public function isListeningTo($event)
  {
    if (!$this->isGetFromActionSpaceEvent($event, [WOOD])) {
      return false;
    }
    return $this->isCollectEvent($event) ||
      $event['actionCardId'] != 'C162_ForestOwner' ||
      Players::getActiveId() == $this->getPlayer()->getId();
  }

  public function onPlayerAfterGain($player, $event)
  {
    return $this->futureMeeplesNode([FOOD => 1], 1);
  }

  public function onPlayerAfterCollect($player, $event)
  {
    return $this->futureMeeplesNode([FOOD => 1], 1);
  }

  public function onPlayerAfterReceive($player, $event)
  {
    return $this->futureMeeplesNode([FOOD => 1], 1);
  }
}
