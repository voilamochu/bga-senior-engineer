<?php
namespace AGR\Cards\E;

class E120_ScrapCollector extends \AGR\Models\Occupation
{
  public function __construct($row)
  {
    parent::__construct($row);
    $this->id = 'E120_ScrapCollector';
    $this->name = clienttranslate('Scrap Collector');
    $this->deck = 'E';
    $this->author = 'beso';
    $this->number = 120;
    $this->category = 'BUILDING_RESOURCES_-_CLAY_(AND_WOOD)';
    $this->desc = [
      clienttranslate(
        'Alternate placing 1 <WOOD> and 1 <CLAY> on each of the next 6 round spaces, starting with <WOOD>. At the start of these rounds, you get the respective resource.'
      ),
    ];
    $this->players = '1+';
  }

  public function onBuy($player)
  {
   return [
      'type' => NODE_SEQ,
      'childs' => [
        $this->futureMeeplesNode([WOOD => 1], ['+1', '+3', '+5']),
        $this->futureMeeplesNode([CLAY => 1], ['+2', '+4', '+6']),
      ]
   ];
 }
}