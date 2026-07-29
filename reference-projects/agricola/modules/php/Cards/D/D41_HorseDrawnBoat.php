<?php
namespace AGR\Cards\D;

class D41_HorseDrawnBoat extends \AGR\Models\MinorImprovement
{
  public function __construct($row)
  {
    parent::__construct($row);
    $this->id = 'D41_HorseDrawnBoat';
    $this->name = clienttranslate('Horse-Drawn Boat');
    $this->deck = 'D';
    $this->number = 41;
    $this->category = GOODS_PROVIDER;
    $this->desc = [
      clienttranslate(
        'Alternate placing 1 <FOOD> and 1 <SHEEP> on each remaining round space, starting with <FOOD>. At the start of these rounds, you get the respective good.'
      ),
    ];
    $this->cost = [
      WOOD => 2,
    ];
    $this->prerequisite = clienttranslate('3 Occupations');
    $this->occupationPrerequisites = ['min' => 3];
  }  

  public function onBuy($player)
  {
    return [
      'type' => NODE_SEQ,
      'childs' => [
        $this->futureMeeplesNode([FOOD => 1], ['+1', '+3', '+5', '+7', '+9', '+11', '+13']),
        $this->futureMeeplesNode([SHEEP => 1], ['+2', '+4', '+6', '+8', '+10', '+12']),
      ]
    ];
  }
}
