<?php
namespace AGR\Cards\C;

use AGR\Core\Globals;

class C37_DwellingMound extends \AGR\Models\MinorImprovement
{
  public function __construct($row)
  {
    parent::__construct($row);
    $this->id = 'C37_DwellingMound';
    $this->name = clienttranslate('Dwelling Mound');
    $this->deck = 'C';
    $this->number = 37;
    $this->category = POINTS_PROVIDER;
    $this->desc = [
      clienttranslate('From now on, you must pay 1 <FOOD> for each new field tile that you place in your farmyard.'),
    ];
    $this->vp = 3;
    $this->cost = [
      FOOD => 1,
    ];
    $this->prerequisite = clienttranslate('Play in Round 3 or Before');
  }

  public function isBuyable($player, $ignoreResources = false, $args = [])
  {
    if (Globals::getTurn() > 3) {
      return false;
    }

    return parent::isBuyable($player, $ignoreResources, $args);
  }

  public function onPlayerComputeCostsPlow($player, &$args)
  {
    // throw new \feException(print_r($args));
    foreach ($args['costs']['trades'] as &$trade) {
      if (!isset($trade[FOOD])) {
        $trade[FOOD] = 0;
      }
      $trade[FOOD]++;
    }
  }
}
