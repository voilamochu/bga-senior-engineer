<?php
namespace AGR\Cards\E;

class E7_Pumpernickel extends \AGR\Models\MinorImprovement
{
  public function __construct($row)
  {
    parent::__construct($row);
    $this->id = 'E7_Pumpernickel';
    $this->name = clienttranslate('Pumpernickel');
    $this->deck = 'E';
    $this->author = 'tamos';
    $this->number = 7;
    $this->category = 'PASSING_-_FOOD';
    $this->desc = [clienttranslate('You immediately get 4 <FOOD>. (Effectively, you are turning 1 <GRAIN> into 4 <FOOD>.)')];
    $this->passing = true;
    $this->cost = [
      GRAIN => 1,
    ];
  }

  public function onBuy($player)
  {
    return $this->gainNode([FOOD => 4]);
  }
}
