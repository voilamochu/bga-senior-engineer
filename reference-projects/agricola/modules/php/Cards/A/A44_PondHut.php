<?php
namespace AGR\Cards\A;

class A44_PondHut extends \AGR\Models\MinorImprovement
{
  public function __construct($row)
  {
    parent::__construct($row);
    $this->id = 'A44_PondHut';
    $this->name = clienttranslate('Pond Hut');
    $this->deck = 'A';
    $this->number = 44;
    $this->category = FOOD_PROVIDER;
    $this->desc = [
      clienttranslate(
        'Place 1 <FOOD> on each of the next 3 round spaces. At the start of these rounds, you get the <FOOD>.'
      ),
    ];
    $this->vp = 1;
    $this->cost = [
      WOOD => 1,
    ];
    $this->prerequisite = clienttranslate('Exactly 2 Occupations');
    $this->occupationPrerequisites = ['equal' => 2];
  }

  public function onBuy($player)
  {
    return $this->futureMeeplesNode([FOOD => 1], 3);
  }
}
