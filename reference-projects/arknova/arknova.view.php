<?php
/**
 *------
 * BGA framework: © Gregory Isabelli <gisabelli@boardgamearena.com> & Emmanuel Colin <ecolin@boardgamearena.com>
 * Ark Nova implementation : © Timothée Pecatte <tim.pecatte@gmail.com>, Vincent Toper <vincent.toper@gmail.com>
 *
 * This code has been produced on the BGA studio platform for use on http://boardgamearena.com.
 * See http://en.boardgamearena.com/#!doc/Studio for more information.
 * -----
 *
 * arknova.view.php
 *
 */

require_once APP_BASE_PATH . 'view/common/game.view.php';

class view_arknova_arknova extends game_view
{
  function getGameName()
  {
    return 'arknova';
  }
  function build_page($viewArgs)
  {
  }
}
