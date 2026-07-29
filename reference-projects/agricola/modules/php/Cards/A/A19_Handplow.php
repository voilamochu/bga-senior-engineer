<?php
namespace AGR\Cards\A;

class A19_Handplow extends \AGR\Models\MinorImprovement
{
  public function __construct($row)
  {
    parent::__construct($row);
    $this->id = 'A19_Handplow';
    $this->name = clienttranslate('Handplow');
    $this->deck = 'A';
    $this->number = 19;
    $this->category = FARM_PLANNER;
    $this->desc = [
      clienttranslate(
        'Add 5 to the current round and place 1 field tile on the corresponding round space. At the start of that round, you can plow the field.'
      ),
    ];
    $this->cost = [
      WOOD => 1,
    ];
  }

  public function onBuy($player)
  {
    return $this->futureMeeplesNode([FIELD => 1], ['+5']);
  }
}
