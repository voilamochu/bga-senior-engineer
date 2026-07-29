<?php
namespace AGR\Cards\A;
use AGR\Core\Globals;

class A33_BigCountry extends \AGR\Models\MinorImprovement
{
  public function __construct($row)
  {
    parent::__construct($row);
    $this->id = 'A33_BigCountry';
    $this->name = clienttranslate('Big Country');
    $this->deck = 'A';
    $this->number = 33;
    $this->category = POINTS_PROVIDER;
    $this->extraVp = true;
    $this->desc = [
      clienttranslate('For each complete round left to play, you immediately get 1 bonus <SCORE> and 2 <FOOD>.'),
    ];
    $this->prerequisite = clienttranslate('All Farmyard Spaces Used');
    $this->bannedStrong3or4p = true;
  }

  public function isBuyable($player, $ignoreResources = false, $args = [])
  {
    if (!empty($player->board()->getFreeZones())) {
      return false;
    }

    return parent::isBuyable($player, $ignoreResources, $args);
  }

  public function onBuy($player)
  {
    $currentTurn = Globals::getTurn();
    $nb = 14 - $currentTurn;

    if ($nb != 0) {
      return $this->gainNode([SCORE => $nb, FOOD => 2 * $nb]);
    }
  }
}
