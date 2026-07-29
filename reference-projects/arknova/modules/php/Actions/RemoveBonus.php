<?php
namespace ARK\Actions;
use ARK\Managers\Meeples;
use ARK\Managers\Players;
use ARK\Core\Notifications;
use ARK\Managers\ActionCards;
use ARK\Core\Engine;
use ARK\Core\Stats;
use ARK\Core\Globals;
use ARK\Helpers\Utils;

class RemoveBonus extends \ARK\Models\Action
{
  public function getState()
  {
    return ST_REMOVE_BONUS;
  }

  public function isAutomatic($player = null)
  {
    return true;
  }

  public function stRemoveBonus()
  {
    $args = $this->getCtxArgs();
    $bonusTiles = Globals::getBonusTiles();
    $bonuses = $bonusTiles[$args['location']];
    foreach ($bonuses as $loc => $bonus) {
      if ($bonus == $args['bonus']) {
        unset($bonusTiles[$args['location']][$loc]);
      }
    }
    if (count($bonusTiles[$args['location']]) == 0) {
      unset($bonusTiles[$args['location']]);
    }
    Globals::setBonusTiles($bonusTiles);

    Notifications::removeBonus($args['bonus'], $bonus);
    $this->resolveAction([]);
  }
}
