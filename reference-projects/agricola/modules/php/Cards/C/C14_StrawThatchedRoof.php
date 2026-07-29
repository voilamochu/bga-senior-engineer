<?php
namespace AGR\Cards\C;

class C14_StrawThatchedRoof extends \AGR\Models\MinorImprovement
{
  public function __construct($row)
  {
    parent::__construct($row);
    $this->id = 'C14_StrawThatchedRoof';
    $this->name = clienttranslate('Straw-Thatched Roof');
    $this->deck = 'C';
    $this->number = 14;
    $this->category = FARM_PLANNER;
    $this->desc = [clienttranslate('You no longer need <REED> to renovate or build a room.')];
    $this->vp = 1;
    $this->prerequisite = clienttranslate('3 Grain Fields');
  }

  public function isBuyable($player, $ignoreResources = false, $args = [])
  {
    $grainFields = $player->board()->getGrainFields();
    if (count($grainFields) < 3) {
      return false;
    }

    return parent::isBuyable($player, $ignoreResources, $args);
  }

  public function orderComputeCostsRenovation()
  {
    return [['<', 'A143_Stonecutter'], ['<', 'B145_BrushwoodCollector']];
  }

  public function onPlayerComputeCostsRenovation($player, &$args)
  {
    $this->removeReed($args);
  }

  public function orderComputeCostsConstruct()
  {
    return [['<', 'A143_Stonecutter'], ['<', 'B145_BrushwoodCollector']];
  }

  public function onPlayerComputeCostsConstruct($player, &$args)
  {
    $this->removeReed($args);
  }

  protected function removeReed(&$args)
  {
    // Keeping reed trades was introducing bugs (eg with Resource Hoarder), just get rid and live without the Saved stat
    if (isset($args['costs']['fees'])) {
      foreach ($args['costs']['fees'] as &$fee) {
        if (isset($fee[REED])) {
          unset($fee[REED]);
          $fee['sources'][] = $this->id;
        }
      }
      unset($fee);
    }

    if (isset($args['costs']['trades'])) {
      foreach ($args['costs']['trades'] as &$trade) {
        if (isset($trade[REED])) {
          unset($trade[REED]);
          $trade['sources'][] = $this->id;
        }
      }
      unset($trade);
    }
  }
}
