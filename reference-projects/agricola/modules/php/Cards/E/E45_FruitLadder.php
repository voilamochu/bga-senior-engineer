<?php
namespace AGR\Cards\E;

class E45_FruitLadder extends \AGR\Models\MinorImprovement
{
  public function __construct($row)
  {
    parent::__construct($row);
    $this->id = 'E45_FruitLadder';
    $this->name = clienttranslate('Fruit Ladder');
    $this->deck = 'E';
    $this->author = 'luki';
    $this->number = 45;
    $this->category = FOOD;
    $this->desc = [
      clienttranslate(
        'Place 1 <FOOD> on each remaining even-numbered round space. At the start of these rounds, you get the <FOOD>.'
      ),
    ];
    $this->vp = 1;
    $this->cost = [
      WOOD => 2,
    ];
  }

  public function onBuy($player)
  {
    return $this->futureMeeplesNode([FOOD => 1], [2, 4, 6, 8, 10, 12, 14]);      
  }
}