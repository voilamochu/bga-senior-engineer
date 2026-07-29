<?php
namespace AGR\Cards\E;

class E8_FarmersMarket extends \AGR\Models\MinorImprovement
{
  public function __construct($row)
  {
    parent::__construct($row);
    $this->id = 'E8_FarmersMarket';
    $this->name = clienttranslate('Farmers Market');
    $this->deck = 'E';
    $this->author = '';
    $this->number = 8;
    $this->category = 'PASSING_-_CROP';
    $this->desc = [clienttranslate('You immediately get 1 <VEGETABLE>. (Effectively, you are buying 1 <VEGETABLE> for 2 <FOOD>.)')];
    $this->passing = true;
    $this->cost = [
      FOOD => 2,
    ];
  }

  public function onBuy($player)
  {
    return $this->gainNode([VEGETABLE => 1]);
  }

}
