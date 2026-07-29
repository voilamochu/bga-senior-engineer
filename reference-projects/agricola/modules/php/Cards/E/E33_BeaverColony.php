<?php
namespace AGR\Cards\E;

class E33_BeaverColony extends \AGR\Models\MinorImprovement
{
  public function __construct($row)
  {
    parent::__construct($row);
    $this->id = 'E33_BeaverColony';
    $this->name = clienttranslate('Beaver Colony');
    $this->deck = 'E';
    $this->author = 'superg';
    $this->number = 33;
    $this->category = 'BONUS_POINTS_-_GET';
    $this->desc = [
      clienttranslate(
        'From now on, one of your pastures with stable cannot hold animals. Each time you get <REED> from an action space, you get 1 bonus <SCORE>.'
      ),
    ];
    $this->vp = 1;
    $this->extraVp = true;
    $this->prerequisite = clienttranslate('1 Fenced Stable');
  }

  public function isBuyable($player, $ignoreResources = false, $args = [])
  {
    $fencedStables = $player->board()->countFencedStables(true);
    if ($fencedStables == 0) {
      return false;
    }
    return parent::isBuyable($player, $ignoreResources, $args);
  }

  public function onBuy($player)
  {
    $player->forceReorganizeIfNeeded();  
  }

  public function isListeningTo($event)
  {
    return $this->isGetFromActionSpaceEvent($event, [REED]);
  }

  public function onPlayerAfterCollect($player, $event)
  {
    return $this->gainNode([SCORE => 1]);
  }

  public function onPlayerAfterGain($player, $event)
  {
    return $this->gainNode([SCORE => 1]);
  }

  public function onPlayerAfterReceive($player, $event)
  {
    return $this->gainNode([SCORE => 1]);
  }

  // Pasture restriction moved to Models/PlayerBoard.php -> getInvalidAnimals()
}
