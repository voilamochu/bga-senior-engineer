<?php
namespace AGR\Cards\C;

use AGR\Helpers\Utils;

class C122_Bricklayer extends \AGR\Models\Occupation
{
  public function __construct($row)
  {
    parent::__construct($row);
    $this->id = 'C122_Bricklayer';
    $this->name = clienttranslate('Bricklayer');
    $this->deck = 'C';
    $this->number = 122;
    $this->category = BUILDING_RESOURCE_PROVIDER;
    $this->desc = [
      clienttranslate(
        'Each improvement and each renovation cost you 1 <CLAY> less. Each room costs you 2 <CLAY> less.'
      ),
    ];
    $this->players = '1+';
  }

  public function onPlayerComputeCardCosts($player, &$args)
  {
    if (!in_array($args['card']->getType(), [MAJOR, MINOR])) {
      return;
    }
    $this->removeClay($args, 1);
  }

  public function orderComputeCostsConstruct()
  {
    return [['>', 'B145_BrushwoodCollector']];
  }

  public function onPlayerComputeCostsConstruct($player, &$args)
  {
    $this->removeClay($args, 2);
  }

  protected function removeClay(&$args, $nb)
  {
    foreach ($args['costs']['trades'] as $trade) {
      if (isset($trade[CLAY])) {
        $trade[CLAY] -= $nb;
        if ($trade[CLAY] <= 0) {
          unset($trade[CLAY]);
        }
        $trade['sources'][] = $this->id;
        $args['costs']['trades'][] = $trade;
      }
    }
  }

  public function orderComputeCostsRenovation()
  {
    return [['>', 'B145_BrushwoodCollector']];
  }

  public function onPlayerComputeCostsRenovation($player, &$args)
  {
    Utils::addBonus($args['costs'], [CLAY => -1], $this->id);
  }
}
