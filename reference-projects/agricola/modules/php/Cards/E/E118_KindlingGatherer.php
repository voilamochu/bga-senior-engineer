<?php
namespace AGR\Cards\E;

class E118_KindlingGatherer extends \AGR\Models\Occupation
{
  public function __construct($row)
  {
    parent::__construct($row);
    $this->id = 'E118_KindlingGatherer';
    $this->name = clienttranslate('Kindling Gatherer');
    $this->deck = 'E';
    $this->author = 'barbarossa89';
    $this->number = 118;
    $this->category = 'BUILDING_RESOURCES_-_WOOD';
    $this->desc = [clienttranslate('Each time you get <FOOD> from an action space, you get 1 additional <WOOD>.')];
    $this->players = '1+';
  }

  public function isListeningTo($event)
  {
    return $this->isCollectEvent($event, FOOD) ||
      ($this->isActionEvent($event, 'Gain') && ($event['fromActionSpace'] ?? false));
  }

  public function onPlayerAfterCollect($player, $event)
  {
    return $this->gainNode([WOOD => 1]);
  }

  public function onPlayerAfterGain($player, $event)
  {
    foreach ($event['meeples'] as $meeple) {
      if ($meeple['type'] == FOOD) {
        return $this->gainNode([WOOD => 1]);
      }
    }
  }
}
