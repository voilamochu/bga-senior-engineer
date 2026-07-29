<?php
namespace AGR\Cards\A;

class A69_LargeGreenhouse extends \AGR\Models\MinorImprovement
{
  public function __construct($row)
  {
    parent::__construct($row);
    $this->id = 'A69_LargeGreenhouse';
    $this->name = clienttranslate('Large Greenhouse');
    $this->deck = 'A';
    $this->number = 69;
    $this->category = CROP_PROVIDER;
    $this->desc = [
      clienttranslate(
        'Add 4, 7, and 9 to the current round and place 1 <VEGETABLE> on each corresponding round space. At the start of these rounds, you get the <VEGETABLE>.'
      ),
    ];
    $this->cost = [
      WOOD => 2,
    ];
    $this->prerequisite = clienttranslate('2 Occupations');
    $this->occupationPrerequisites = ['min' => 2];
  }

  public function onBuy($player)
  {
    return $this->futureMeeplesNode([VEGETABLE => 1], ['+4', '+7', '+9']);
  }
}
