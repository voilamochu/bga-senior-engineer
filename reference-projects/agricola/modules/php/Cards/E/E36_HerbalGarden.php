<?php
namespace AGR\Cards\E;

class E36_HerbalGarden extends \AGR\Models\MinorImprovement
{
  public function __construct($row)
  {
    parent::__construct($row);
    $this->id = 'E36_HerbalGarden';
    $this->name = clienttranslate('Herbal Garden');
    $this->deck = 'E';
    $this->author = 'chris';
    $this->number = 36;
    $this->category = 'BONUS_POINTS_-_GET';
    $this->desc = [clienttranslate('From now on, at least one of your pastures must contain no animals.')];
    $this->vp = 2;
    $this->cost = [
      WOOD => 1,
    ];
    $this->prerequisite = clienttranslate('1 Pasture');
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
    $player->forceReorganizeIfNeeded();  
  }

  // Pasture restriction moved to Models/PlayerBoard.php -> getInvalidAnimals()
}
