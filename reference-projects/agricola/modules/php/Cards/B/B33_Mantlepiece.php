<?php
namespace AGR\Cards\B;
use AGR\Core\Globals;

class B33_Mantlepiece extends \AGR\Models\MinorImprovement
{
  public function __construct($row)
  {
    parent::__construct($row);
    $this->id = 'B33_Mantlepiece';
    $this->name = clienttranslate('Mantlepiece');
    $this->deck = 'B';
    $this->number = 33;
    $this->category = POINTS_PROVIDER;
    $this->desc = [
      clienttranslate(
        'When you play this card, you immediately get 1 bonus <SCORE> for each complete round left to play. You may no longer renovate your house.'
      ),
    ];
    $this->vp = -3;
    $this->extraVp = true;
    $this->cost = [
      STONE => 1,
    ];
    $this->prerequisite = clienttranslate('Clay or Stone House');
  }

  public function isBuyable($player, $ignoreResources = false, $args = [])
  {
    if ($player->getRoomType() == 'roomWood') {
      return false;
    }

    return parent::isBuyable($player, $ignoreResources, $args);
  }

  public function onBuy($player)
  {
    return $this->gainNode([SCORE => 14 - Globals::getTurn()]);
  }
}
