<?php
namespace AGR\Cards\C;

class C118_WoodCollector extends \AGR\Models\Occupation
{
  public function __construct($row)
  {
    parent::__construct($row);
    $this->id = 'C118_WoodCollector';
    $this->name = clienttranslate('Wood Collector');
    $this->deck = 'C';
    $this->number = 118;
    $this->category = BUILDING_RESOURCE_PROVIDER;
    $this->desc = [
      clienttranslate(
        'Place 1 <WOOD> on each of the next 5 round spaces. At the start of these rounds, you get the <WOOD>.'
      ),
    ];
    $this->players = '1+';
  }

  public function onBuy($player)
  {
    return $this->futureMeeplesNode([WOOD => 1], 5);
  }
}
