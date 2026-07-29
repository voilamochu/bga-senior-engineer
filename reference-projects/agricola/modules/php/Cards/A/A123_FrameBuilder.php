<?php
namespace AGR\Cards\A;
use AGR\Helpers\Utils;

class A123_FrameBuilder extends \AGR\Models\Occupation
{
  public function __construct($row)
  {
    parent::__construct($row);
    $this->id = 'A123_FrameBuilder';
    $this->name = clienttranslate('Frame Builder');
    $this->deck = 'A';
    $this->number = 123;
    $this->category = BUILDING_RESOURCE_PROVIDER;
    $this->desc = [
      clienttranslate(
        'Each time you build a room/renovate, but only once per room/action, you can replace exactly 2 <CLAY> or 2 <STONE> with 1 <WOOD>.'
      ),
    ];
    $this->players = '1+';

    $this->rulings = [
      clienttranslate('The effect may be applied once for each room built.'),
    ];
  }

  public function orderComputeCostsRenovation()
  {
    return [['>', 'B145_BrushwoodCollector'], ['>', 'A143_Stonecutter']];
  }

  public function onPlayerComputeCostsRenovation($player, &$args)
  {
    $choices = [[WOOD => 1, CLAY => -2], [WOOD => 1, STONE => -2]];
    Utils::addBonusChoices($args['costs'], $choices, $this->id, true);
  }

  public function orderComputeCostsConstruct()
  {
    return [['>', 'B145_BrushwoodCollector'], ['>', 'A143_Stonecutter']];
  }

  public function onPlayerComputeCostsConstruct($player, &$args)
  {
    foreach ($args['costs']['trades'] as $trade) {
      $nonEmpty = false;
      if (isset($trade[CLAY]) && $trade[CLAY] >= 2) {
        $trade[CLAY] -= 2;
        $trade[WOOD] = ($trade[WOOD] ?? 0) + 1;
        $trade['sources'][] = $this->id;
        $nonEmpty = true;
      } elseif (isset($trade[STONE]) && $trade[STONE] >= 2) {
        $trade[STONE] -= 2;
        $trade[WOOD] = ($trade[WOOD] ?? 0) + 1;
        $trade['sources'][] = $this->id;
        $nonEmpty = true;
      }

      if ($nonEmpty) {
        Utils::addCost($args['costs'], $trade);
      }
    }
  }
}
