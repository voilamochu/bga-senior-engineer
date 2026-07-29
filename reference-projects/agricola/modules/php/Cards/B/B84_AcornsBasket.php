<?php
namespace AGR\Cards\B;

class B84_AcornsBasket extends \AGR\Models\MinorImprovement
{
  public function __construct($row)
  {
    parent::__construct($row);
    $this->id = 'B84_AcornsBasket';
    $this->name = clienttranslate('Acorns Basket');
    $this->deck = 'B';
    $this->number = 84;
    $this->category = LIVESTOCK_PROVIDER;
    $this->desc = [
      clienttranslate(
        'Place 1 <PIG> on each of the next 2 round spaces. At the start of these rounds, you get the <PIG>.'
      ),
    ];
    $this->cost = [
      REED => 1,
    ];
    $this->prerequisite = clienttranslate('3 Occupations');
    $this->occupationPrerequisites = ['min' => 3];
  }

  public function onBuy($player)
  {
    return $this->futureMeeplesNode([PIG => 1], 2);
  }
}
