<?php
namespace AGR\Cards\A;

use AGR\Helpers\Utils;

class A16_RammedClay extends \AGR\Models\MinorImprovement
{
  public function __construct($row)
  {
    parent::__construct($row);
    $this->id = 'A16_RammedClay';
    $this->name = clienttranslate('Rammed Clay');
    $this->deck = 'A';
    $this->number = 16;
    $this->category = FARM_PLANNER;
    $this->desc = [
      clienttranslate(
        'When you play this card, you immediately get 1 <CLAY>. You can use <CLAY> instead of <WOOD> to build fences.'
      ),
    ];

    $this->rulings = [
      clienttranslate('You can use both <WOOD> and <CLAY> for the same __Build Fences__ action.'),
    ];
  }

  public function onBuy($player)
  {
    return $this->gainNode([CLAY => 1]);
  }

  public function onPlayerComputeCostsFencing($player, &$args)
  {
    if (!empty($args['costs']['constraints'] ?? [])) {
      return;
    }
    foreach ($args['costs']['trades'] as $trade) {
      $keys = array_diff(array_keys($trade), ['nb', 'max']);

      if ($keys == [WOOD]) {
        $trade[CLAY] = $trade[WOOD];
        $trade['sources'][] = $this->id;
        unset($trade[WOOD]);
        Utils::addCost($args['costs'], $trade);
      }
    }
  }
}
