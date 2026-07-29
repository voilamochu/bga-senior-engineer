<?php
namespace AGR\Cards\D;
use AGR\Core\Globals;

class D37_Sculpture extends \AGR\Models\MinorImprovement
{
  public function __construct($row)
  {
    parent::__construct($row);
    $this->id = 'D37_Sculpture';
    $this->name = clienttranslate('Sculpture');
    $this->deck = 'D';
    $this->number = 37;
    $this->category = POINTS_PROVIDER;
    $this->desc = [
      clienttranslate(
        'You can only play this card if there are more complete rounds left to play than you have unused farmyard spaces.'
      ),
    ];
    $this->vp = 2;
    $this->cost = [
      STONE => 1,
    ];
    $this->prerequisite = clienttranslate('see below');
    $this->bannedWeak1or2p = true;
  }

  public function isBuyable($player, $ignoreResources = false, $args = [])
  {
    $turnLeft = 14 - Globals::getTurn();
    $n = count($player->board()->getFreeZones());

    if ($turnLeft <= $n) {
      return false;
    }

    return parent::isBuyable($player, $ignoreResources, $args);
  }
}
