<?php
namespace AGR\Actions;

use AGR\Managers\Meeples;
use AGR\Core\Globals;
use AGR\Core\Engine;
use AGR\Core\Notifications;
use AGR\Managers\Players;

class FirstPlayer extends \AGR\Models\Action
{
  public function getState()
  {
    return ST_FIRSTPLAYER;
  }

  public function stFirstPlayer()
  {
    $activePlayer = Players::getActiveId();
    $tokenId = Meeples::collectFirstPlayerToken($activePlayer);
    Globals::setFirstPlayer($activePlayer);
    Notifications::firstPlayer(Players::getActive(), $tokenId);
    $this->resolveAction();
  }

  public function isAutomatic($player = null)
  {
    return true;
  }
}
