<?php
namespace AGR\Cards\C;
use AGR\Managers\Players;

class C32_AbortOriel extends \AGR\Models\MinorImprovement
{
  public function __construct($row)
  {
    parent::__construct($row);
    $this->id = 'C32_AbortOriel';
    $this->name = clienttranslate('Abort Oriel');
    $this->deck = 'C';
    $this->number = 32;
    $this->category = POINTS_PROVIDER;
    $this->desc = [
      clienttranslate(
        'You can no longer play this card when any player (including you) has 5 or more cards in front of them.'
      ),
    ];
    $this->vp = 3;
    $this->cost = [
      CLAY => 2,
    ];
    $this->prerequisite = clienttranslate('see below');
    $this->isCorbariusOrDulcinaria = true;

    $this->rulings = [
      clienttranslate('This card may be played as your fifth card.'),
    ];
  }
  
  public function isBuyable($player, $ignoreResources = false, $args = [])
  {
    foreach (Players::getAll() as $player2) {
      if ($player2->getPlayedCards()->count() >= 5) {
        return false;
      }
    }

    return parent::isBuyable($player, $ignoreResources, $args);
  }  
}
