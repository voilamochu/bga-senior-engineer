<?php
namespace AGR\Cards\E;

class E44_FodderBeets extends \AGR\Models\MinorImprovement
{
  public function __construct($row)
  {
    parent::__construct($row);
    $this->id = 'E44_FodderBeets';
    $this->name = clienttranslate('Fodder Beets');
    $this->deck = 'E';
    $this->author = 'letsdance';
    $this->number = 44;
    $this->category = 'FOOD_-_FUTURE_ROUND_SPACES';
    $this->desc = [
      clienttranslate(
        'Place 1 <FOOD> on each remaining odd-numbered round space. At the start of these rounds, you get the <FOOD>.'
      ),
    ];
    $this->vp = 1;
    $this->prerequisite = clienttranslate('3 Field Tiles');
    $this->implemented = true;
  }

  public function isBuyable($player, $ignoreResources = false, $args = [])
  {
    $fields = $player->board()->getFieldTiles();
    if (count($fields) < 3) {
      return false;
    }
    return parent::isBuyable($player, $ignoreResources, $args);
  }

  public function onBuy($player)
  {
    return $this->futureMeeplesNode([FOOD => 1], [3, 5, 7, 9, 11, 13]);      
  }
}