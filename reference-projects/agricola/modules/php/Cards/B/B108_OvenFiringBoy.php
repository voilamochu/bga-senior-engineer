<?php
namespace AGR\Cards\B;

class B108_OvenFiringBoy extends \AGR\Models\Occupation
{
  public function __construct($row)
  {
    parent::__construct($row);
    $this->id = 'B108_OvenFiringBoy';
    $this->name = clienttranslate('Oven Firing Boy');
    $this->deck = 'B';
    $this->number = 108;
    $this->category = FOOD_PROVIDER;
    $this->desc = [
      clienttranslate('Each time you use a wood accumulation space, you get an additional __Bake Bread__ action.'),
    ];
    $this->players = '1+';
  }

  public function isListeningTo($event)
  {
    return $this->isBeforeCollectEvent($event, WOOD);
  }

  public function onPlayerPlaceFarmer($player, $event)
  {
    return $this->bakeBreadNode();
  }

  // TODO: remove
  public function onPlayerAfterCollect($player, $event)
  {
    return $this->bakeBreadNode();
  }  
}
