<?php
namespace AGR\Cards\B;

class B8_MarketStall extends \AGR\Models\MinorImprovement
{
  public function __construct($row)
  {
    parent::__construct($row);
    $this->id = 'B8_MarketStall';
    $this->name = clienttranslate('Market Stall');
    $this->deck = 'B';
    $this->number = 8;
    $this->category = CROP_PROVIDER;
    $this->desc = [
      clienttranslate(
        'You immediately get 1 <VEGETABLE>. (Effectively, you are exchanging 1 <GRAIN> for 1 <VEGETABLE>).'
      ),
    ];
    $this->passing = true;
    $this->cost = [
      GRAIN => 1,
    ];
  }

  public function onBuy($player)
  {
    return $this->gainNode([VEGETABLE => 1]);
  }
}
