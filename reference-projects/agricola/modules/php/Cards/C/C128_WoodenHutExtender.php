<?php
namespace AGR\Cards\C;
use AGR\Helpers\Utils;
use AGR\Core\Globals;

class C128_WoodenHutExtender extends \AGR\Models\Occupation
{
  public function __construct($row)
  {
    parent::__construct($row);
    $this->id = 'C128_WoodenHutExtender';
    $this->name = clienttranslate('Wooden Hut Extender');
    $this->deck = 'C';
    $this->number = 128;
    $this->isCorbariusOrDulcinaria = true;
    $this->category = FARM_PLANNER;
    $this->desc = [
      clienttranslate(
        'Wood rooms now cost you 1 <REED>, and additionally 5 <WOOD> through round 5, 4 <WOOD>  in rounds 6 and 7, and 3 <WOOD>  in round 8 and later.'
      ),
    ];
    $this->players = '3+';
    $this->replacesCostFor = ['ComputeCostsConstruct'];
  }

  public function onPlayerComputeCostsConstruct($player, &$args)
  {
    $turn = Globals::getTurn();
    if ($args['type'] == 'roomWood') {
      $cost = 5;
      if ($turn == 6 || $turn == 7) {
        $cost = 4;
      }
      if ($turn >= 8) {
        $cost = 3;
      }

      Utils::addCost($args['costs'], [WOOD => $cost, REED => 1], $this->id);
    }
  }
}
