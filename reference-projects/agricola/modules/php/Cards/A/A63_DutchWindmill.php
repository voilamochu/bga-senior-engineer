<?php

namespace AGR\Cards\A;

use AGR\Core\Globals;
use AGR\Models\MinorImprovement;

class A63_DutchWindmill extends MinorImprovement
{
  public function __construct($row)
  {
    parent::__construct($row);
    $this->id = 'A63_DutchWindmill';
    $this->name = clienttranslate('Dutch Windmill');
    $this->deck = 'A';
    $this->number = 63;
    $this->category = FOOD_PROVIDER;
    $this->desc = [
      clienttranslate(
        'Each time you take a __Bake Bread__ action in a round immediately following a harvest, you get 3 additional <FOOD>.'
      ),
    ];
    $this->vp = 2;
    $this->cost = [
      WOOD => 2,
      STONE => 2,
    ];
  }

  public function isListeningTo($event)
  {
    return $this->isActionEvent($event, 'Exchange') &&
      $event['trigger'] == BREAD &&
      in_array(Globals::getTurn(), [5, 8, 10, 12, 14]);
  }

  public function onPlayerAfterExchange($player, $args)
  {
    return $this->gainNode([FOOD => 3]);
  }
}
