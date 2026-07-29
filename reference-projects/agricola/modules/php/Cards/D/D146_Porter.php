<?php
namespace AGR\Cards\D;

class D146_Porter extends \AGR\Models\Occupation
{
  public function __construct($row)
  {
    parent::__construct($row);
    $this->id = 'D146_Porter';
    $this->name = clienttranslate('Porter');
    $this->deck = 'D';
    $this->number = 146;
    $this->category = BUILDING_RESOURCE_PROVIDER;
    $this->desc = [
      clienttranslate(
        'Each time you take at least 4 of the same building resource from an accumulation space, you get 1 additional building resource of the accumulating type and 1 <FOOD>'
      ),
    ];
    $this->players = '3+';
  }

  public function isListeningTo($event)
  {
    return $this->isCollectEvent($event) && !empty($this->getGains($event));
  }

  public function getGains($event)
  {
    $meeples = [WOOD => 0, CLAY => 0, REED => 0, STONE => 0];
    foreach ($event['meeples'] as $meeple) {
      $type = $meeple['type'];
      if (\array_key_exists($type, $meeples)) {
        $meeples[$type]++;
      }
    }

    $gains = [];
    foreach ($meeples as $type => $n) {
      if ($n >= 4) {
        $gains[$type] = 1;
      }
    }

    if (!empty($gains)) {
      $gains[FOOD] = 1;
    }

    return $gains;
  }

  public function onPlayerAfterCollect($player, $event)
  {
    $gains = $this->getGains($event);
    return $this->gainNode($gains);
  }
}
