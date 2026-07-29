<?php
namespace AGR\Cards\A;

use AGR\Core\Engine;

class A119_FirewoodCollector extends \AGR\Models\Occupation
{
  public function __construct($row)
  {
    parent::__construct($row);
    $this->id = 'A119_FirewoodCollector';
    $this->name = clienttranslate('Firewood Collector');
    $this->deck = 'A';
    $this->number = 119;
    $this->category = BUILDING_RESOURCE_PROVIDER;
    $this->desc = [
      clienttranslate(
        'Each time you use the __Farmland__, __Grain Seeds__, __Grain Utilization__, or __Cultivation__ action space, at the end of that turn, you get 1 <WOOD>.'
      ),
    ];
    $this->players = '1+';
  }

  public function isListeningTo($event)
  {
    return $this->isActionEvent($event, 'PlaceFarmer') &&
      in_array($event['actionCardType'], ['Farmland', 'GrainSeeds', 'GrainUtilization', 'Cultivation']);
  }

  public function onPlayerAfterPlaceFarmer($player, $args)
  {
    return $this->gainNode([WOOD => 1]);
  }
}
