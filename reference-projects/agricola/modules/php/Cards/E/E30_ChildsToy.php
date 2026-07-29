<?php
namespace AGR\Cards\E;
use AGR\Core\Notifications;

class E30_ChildsToy extends \AGR\Models\MinorImprovement
{
  public function __construct($row)
  {
    parent::__construct($row);
    $this->id = 'E30_ChildsToy';
    $this->name = clienttranslate("Child's Toy");
    $this->deck = 'E';
    $this->author = 'beso';
    $this->number = 30;
    $this->category = 'BONUS_POINTS_-_GET';
    $this->desc = [
      clienttranslate('During the feeding phase of each harvest, your newborns require 2 <FOOD> (instead of 1).'),
    ];
    $this->vp = 2;
    $this->costs = [[WOOD => 1], [CLAY => 1]];
    $this->prerequisite = clienttranslate('Exactly 2 Adults');
  }

  public function isBuyable($player, $ignoreResources = false, $args = [])
  {
    if ($player->countFarmers(ADULT) != 2) {
      return false;
    }

    return parent::isBuyable($player, $ignoreResources, $args);
  }

  public function onBuy($player) 
  {
    Notifications::updateHarvestCosts();
  }

  // Rest of functionality in models/Player.php -> getHarvestCost()
}
