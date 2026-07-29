<?php
namespace AGR\Cards\B;

class B66_SackCart extends \AGR\Models\MinorImprovement
{
  public function __construct($row)
  {
    parent::__construct($row);
    $this->id = 'B66_SackCart';
    $this->name = clienttranslate('Sack Cart');
    $this->deck = 'B';
    $this->number = 66;
    $this->category = CROP_PROVIDER;
    $this->desc = [
      clienttranslate(
        'Place 1 <GRAIN> each on the remaining spaces for rounds 5, 8, 11, and 14. At the start of these rounds, you get the <GRAIN>.'
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
    return $this->futureMeeplesNode([GRAIN => 1], [5, 8, 11, 14]);   
  }
}
