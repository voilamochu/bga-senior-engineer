<?php
namespace AGR\Cards\D;

class D9_GameTrade extends \AGR\Models\MinorImprovement
{
  public function __construct($row)
  {
    parent::__construct($row);
    $this->id = 'D9_GameTrade';
    $this->name = clienttranslate('Game Trade');
    $this->deck = 'D';
    $this->number = 9;
    $this->category = LIVESTOCK_PROVIDER;
    $this->desc = [
      clienttranslate(
        'You immediately get 1 <PIG> and 1 <CATTLE>. (effectively, you are exchanging 2 <SHEEP> for 1 <PIG> and 1 <CATTLE>.)'
      ),
    ];
    $this->passing = true;
    $this->cost = [
      SHEEP => 2,
    ];
  }

  public function onBuy($player)
  {
    return $this->gainNode([
      PIG => 1,
      CATTLE => 1,
    ]);
  }
}
