<?php
namespace AGR\Cards\C;

class C83_EarlyCattle extends \AGR\Models\MinorImprovement
{
  public function __construct($row)
  {
    parent::__construct($row);
    $this->id = 'C83_EarlyCattle';
    $this->name = clienttranslate('Early Cattle');
    $this->deck = 'C';
    $this->number = 83;
    $this->category = LIVESTOCK_PROVIDER;
    $this->desc = [clienttranslate('When you play this card, you immediately get 2 <CATTLE>.')];
    $this->vp = -3;
    $this->prerequisite = clienttranslate('1 Pasture');
    $this->isCorbariusOrDulcinaria = true;
  }

  public function isBuyable($player, $ignoreResources = false, $args = [])
  {
    $pastures = count($player->board()->getPastures(true));
    if ($pastures == 0) {
      return false;
    }
    return parent::isBuyable($player, $ignoreResources, $args);
  }

  public function onBuy($player)
  {
    return $this->gainNode([CATTLE => 2]);
  }
}
