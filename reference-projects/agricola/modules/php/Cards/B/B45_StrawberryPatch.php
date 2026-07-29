<?php
namespace AGR\Cards\B;

use AGR\Helpers\Utils;
use AGR\Managers\Meeples;

class B45_StrawberryPatch extends \AGR\Models\MinorImprovement
{
  public function __construct($row)
  {
    parent::__construct($row);
    $this->id = 'B45_StrawberryPatch';
    $this->name = clienttranslate('Strawberry Patch');
    $this->deck = 'B';
    $this->number = 45;
    $this->category = FOOD_PROVIDER;
    $this->desc = [
      clienttranslate(
        'Place 1 <FOOD> on each of the next 3 round spaces. At the start of these rounds, you get the <FOOD>.'
      ),
    ];
    $this->vp = 2;
    $this->cost = [
      WOOD => 1,
    ];
    $this->prerequisite = clienttranslate('2 Vegetable Fields');
  }

  public function isBuyable($player, $ignoreResources = false, $args = [])
  {
    $vegFields = $player->board()->getVegetableFields();
    if (count($vegFields) < 2) {
      return false;
    }

    return parent::isBuyable($player, $ignoreResources, $args);
  }

  public function onBuy($player)
  {
    return $this->futureMeeplesNode([FOOD => 1], 3);      
  }
}
