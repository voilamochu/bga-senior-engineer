<?php
namespace AGR\Cards\E;

class E46_WaterlilyPond extends \AGR\Models\MinorImprovement
{
  public function __construct($row)
  {
    parent::__construct($row);
    $this->id = 'E46_WaterlilyPond';
    $this->name = clienttranslate('Waterlily Pond');
    $this->deck = 'E';
    $this->author = 'hako';
    $this->number = 46;
    $this->category = 'FOOD_-_FUTURE_ROUND_SPACES';
    $this->desc = [
      clienttranslate(
        'Place 1 <FOOD> on each of the next 2 round spaces. At the start of these rounds, you get the <FOOD>.'
      ),
    ];
    $this->vp = 1;
    $this->prerequisite = clienttranslate('Exactly 2 Occupations');
    $this->occupationPrerequisites = ['equal' => 2];
  }

  public function onBuy($player)
  {
    return $this->futureMeeplesNode([FOOD => 1], 2);
  }
}
