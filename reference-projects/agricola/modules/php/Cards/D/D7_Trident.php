<?php
namespace AGR\Cards\D;
use AGR\Core\Globals;

class D7_Trident extends \AGR\Models\MinorImprovement
{
  public function __construct($row)
  {
    parent::__construct($row);
    $this->id = 'D7_Trident';
    $this->name = clienttranslate('Trident');
    $this->deck = 'D';
    $this->number = 7;
    $this->category = FOOD_PROVIDER;
    $this->desc = [clienttranslate('If you play this card in round 3/6/9/12, you immediately get 3/4/5/6 <FOOD>.')];
    $this->passing = true;
    $this->cost = [
      WOOD => 1,
    ];
    $this->prerequisite = clienttranslate('Play in Round 3, 6, 9, or 12');
  }

  public function isBuyable($player, $ignoreResources = false, $args = [])
  {
    $turn = Globals::getTurn();
    if (!in_array($turn, [3, 6, 9, 12])) {
      return false;
    }
    return parent::isBuyable($player, $ignoreResources, $args);
  }

  public function onBuy($player)
  {
    $turn = Globals::getTurn();
    $gains = [0, 0, 0, 3, 0, 0, 4, 0, 0, 5, 0, 0, 6, 0, 0]; // TODO : check gains for intermediate rounds.
    $gain = $gains[$turn];
    if ($gain > 0) {
      return $this->gainNode([FOOD => $gain]);
    }
  }
}
