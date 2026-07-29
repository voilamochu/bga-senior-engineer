<?php
namespace AGR\Cards\A;
use AGR\Helpers\Utils;

class A143_Stonecutter extends \AGR\Models\Occupation
{
  public function __construct($row)
  {
    parent::__construct($row);
    $this->id = 'A143_Stonecutter';
    $this->name = clienttranslate('Stonecutter');
    $this->deck = 'A';
    $this->number = 143;
    $this->category = BUILDING_RESOURCE_PROVIDER;
    $this->desc = [clienttranslate('Every improvement, room, and renovation costs you 1 <STONE> less.')];
    $this->players = '3+';
  }

  public function onPlayerComputeCardCosts($player, &$args)
  {
    if (!in_array($args['card']->getType(), [MAJOR, MINOR])) {
      return;
    }
    $this->removeOneStone($args);
  }

  public function orderComputeCostsConstruct()
  {
    return [['>', 'B145_BrushwoodCollector']];
  }

  public function onPlayerComputeCostsConstruct($player, &$args)
  {
    $this->removeOneStone($args);
  }

  public function orderComputeCostsRenovation()
  {
    return [['>', 'B145_BrushwoodCollector']];
  }

  public function onPlayerComputeCostsRenovation($player, &$args)
  {
    Utils::addBonus($args['costs'], [STONE => -1], $this->id);
  }

  protected function removeOneStone(&$args)
  {
    foreach ($args['costs']['trades'] as $trade) {
      if (isset($trade[STONE])) {
        $trade[STONE]--;
        if ($trade[STONE] <= 0) {
          unset($trade[STONE]);
        }
        $trade['sources'][] = $this->id;
        $args['costs']['trades'][] = $trade;
      }
    }
  }
}
