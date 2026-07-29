<?php

namespace AGR\Cards\B;

use AGR\Models\Occupation;
use function array_key_exists;

class B162_ForestClearer extends Occupation
{
  public function __construct($row)
  {
    parent::__construct($row);
    $this->id = 'B162_ForestClearer';
    $this->name = clienttranslate('Forest Clearer');
    $this->deck = 'B';
    $this->number = 162;
    $this->category = BUILDING_RESOURCE_PROVIDER;
    $this->desc = [
      clienttranslate(
        'Each time you obtain exactly 2/3/4 <WOOD> from a wood accumulation space, you get 1 additional <WOOD> and 1/0/1 <FOOD>.'
      ),
    ];
    $this->players = '4+';
    $this->isArtifexOrBubulcus = true;
  }

  public function isListeningTo($event)
  {
    return $this->isCollectEvent($event) && !empty($this->getGains($event));
  }

  public function getGains($event)
  {
    $meeples = [WOOD => 0];
    foreach ($event['meeples'] as $meeple) {
      $type = $meeple['type'];
      if (array_key_exists($type, $meeples)) {
        $meeples[$type]++;
      }
    }

    $gains = [];
    foreach ($meeples as $type => $n) {
      if ($n >= 2 && $n <= 4) {
        $gains[$type] = 1;
      }

      if ($n == 2 || $n == 4) {
        $gains[FOOD] = 1;
      }
    }

    return $gains;
  }

  public function onPlayerAfterCollect($player, $event)
  {
    $gains = $this->getGains($event);
    return $this->gainNode($gains);
  }
}
