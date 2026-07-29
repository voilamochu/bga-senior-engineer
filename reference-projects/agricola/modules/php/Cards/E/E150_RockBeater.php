<?php
namespace AGR\Cards\E;

class E150_RockBeater extends \AGR\Models\Occupation
{
  public function __construct($row)
  {
    parent::__construct($row);
    $this->id = 'E150_RockBeater';
    $this->name = clienttranslate('Rock Beater');
    $this->deck = 'E';
    $this->author = 'chris';
    $this->number = 150;
    $this->category = 'ACTION';
    $this->desc = [
      clienttranslate(
        'You can use an action space providing both stone and a different building resource even if it is occupied by another player. Stone rooms cost you 2 <STONE> less each.'
      ),
    ];
    $this->players = '4+';
  }

  public function onPlayerComputeArgsPlaceFarmer($player)
  {
    $added = [];
    $added[] = ['actionCardId' => 'ActionResourceMarket4', 'playerConstraint' => $player->getId(), 'ignoreResources' => true];
    return $added;
  }

  public function orderComputeCostsConstruct()
  {
    return [['>', 'B145_BrushwoodCollector']];
  }

  public function onPlayerComputeCostsConstruct($player, &$args)
  {
    $this->removeOneStone($args);
  }

  protected function removeOneStone(&$args)
  {
    foreach ($args['costs']['trades'] as $trade) {
      if (isset($trade[STONE])) {
          $trade[STONE] -= 2;
        if ($trade[STONE] <= 0) {
          unset($trade[STONE]);
        }
        $trade['sources'][] = $this->id;
        $args['costs']['trades'][] = $trade;
      }
    }
  }

}
