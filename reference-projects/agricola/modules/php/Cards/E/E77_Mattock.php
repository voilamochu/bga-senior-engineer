<?php
namespace AGR\Cards\E;

class E77_Mattock extends \AGR\Models\MinorImprovement
{
  public function __construct($row)
  {
    parent::__construct($row);
    $this->id = 'E77_Mattock';
    $this->name = clienttranslate('Mattock');
    $this->deck = 'E';
    $this->author = 'chris';
    $this->number = 77;
    $this->category = 'BUILDING_RESOURCES_-_CLAY';
    $this->desc = [
      clienttranslate('Each time you get <REED> and/or <STONE> from an action space, you get 1 additional <CLAY>.'),
    ];
    $this->cost = [
      WOOD => 1,
    ];
  }

  public function isListeningTo($event)
  {
    return $this->isGetFromActionSpaceEvent($event, [REED, STONE]);
  }

  public function onPlayerAfterGain($player, $event)
  {
    return $this->gainNode([CLAY => 1]);
  }

  public function onPlayerAfterCollect($player, $event)
  {
    return $this->gainNode([CLAY => 1]);
  }

  public function onPlayerAfterReceive($player, $event)
  {
    return $this->gainNode([CLAY => 1]);
  }
}
