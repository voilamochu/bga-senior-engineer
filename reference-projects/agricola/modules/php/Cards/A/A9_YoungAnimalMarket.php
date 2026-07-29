<?php
namespace AGR\Cards\A;

class A9_YoungAnimalMarket extends \AGR\Models\MinorImprovement
{
  public function __construct($row)
  {
    parent::__construct($row);
    $this->id = 'A9_YoungAnimalMarket';
    $this->name = clienttranslate('Young Animal Market');
    $this->deck = 'A';
    $this->number = 9;
    $this->category = LIVESTOCK_PROVIDER;
    $this->desc = [
      clienttranslate('You immediately get 1 <CATTLE>. (Effectively, you are exchanging 1 <SHEEP> for 1 <CATTLE>.)'),
    ];
    $this->passing = true;
    $this->cost = [
      SHEEP => '1',
    ];
  }

  public function onBuy($player)
  {
    return $this->gainNode([CATTLE => 1]);
  }
}
