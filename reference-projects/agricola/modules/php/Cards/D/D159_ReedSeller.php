<?php
namespace AGR\Cards\D;

class D159_ReedSeller extends \AGR\Models\Occupation
{
  public function __construct($row)
  {
    parent::__construct($row);
    $this->id = 'D159_ReedSeller';
    $this->name = ('Reed Seller');
    $this->deck = 'D';
    $this->number = 159;
    $this->isCorbariusOrDulcinaria = true;
    $this->category = FOOD_PROVIDER;
    $this->desc = [
      (
        'At any time, you can turn 1 reed into 3 food. Any other player can prevent this by buying the reed for 2 food from you. If multiple players are interested, choose one.'
      ),
    ];
    $this->players = '4+';
    $this->implemented = false;
  }
}
