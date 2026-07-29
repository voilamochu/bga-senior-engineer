<?php
namespace AGR\Cards\D;

class D43_Hutch extends \AGR\Models\MinorImprovement
{
  public function __construct($row)
  {
    parent::__construct($row);
    $this->id = 'D43_Hutch';
    $this->name = clienttranslate('Hutch');
    $this->deck = 'D';
    $this->number = 43;
    $this->category = FOOD_PROVIDER;
    $this->desc = [
      clienttranslate(
        'Place 0, 1, 2, and 3 <FOOD> in this order on the next 4 round spaces. At the start of these rounds, you get the <FOOD>.'
      ),
    ];
    $this->vp = 1;
    $this->cost = [
      WOOD => 1,
      REED => 1,
    ];
  }

  public function onBuy($player)
  {
    return [
      'type' => NODE_SEQ,
      'childs' => [
        $this->futureMeeplesNode([FOOD => 1], ['+2']),
        $this->futureMeeplesNode([FOOD => 2], ['+3']),
        $this->futureMeeplesNode([FOOD => 3], ['+4']),
      ]
    ];
  }
}
