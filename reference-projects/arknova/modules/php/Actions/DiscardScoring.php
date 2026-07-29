<?php

namespace ARK\Actions;

use ARK\Managers\Meeples;
use ARK\Managers\Players;
use ARK\Core\Notifications;
use ARK\Managers\ZooCards;
use ARK\Core\Engine;
use ARK\Core\Game;
use ARK\Core\Globals;
use ARK\Helpers\Utils;
use ARK\Models\Player;

class DiscardScoring extends \ARK\Models\Action
{
  public function getState(): int
  {
    return ST_DISCARD_SCORING;
  }

  public function getDescription(): string|array
  {
    return clienttranslate('Discard Final scoring card (all players)');
  }

  public function isIrreversible(?Player $player = null): bool
  {
    return !Globals::isSolo();
  }

  public function argsDiscardScoring()
  {
    $args = [];
    foreach (Players::getAll() as $pId => $player) {
      $hand = $player->getScoringHand();
      $args['_private'][$pId] = [
        'cardIds' => $hand->getIds(),
      ];
    }
    return $args;
  }

  public function stPreDiscardScoring()
  {
    // added to force trigger of Venom token issue
  }

  public function stDiscardScoring()
  {
    Globals::setEndFinalScoringDone(true);
  }

  public function actDiscardScoring($cardId)
  {
    // Sanity checks
    self::checkAction('actDiscardScoring');
    $player = Players::getCurrent();
    $args = $this->argsDiscardScoring();
    if (!in_array($cardId, $args['_private'][$player->getId()]['cardIds'])) {
      throw new \BgaVisibleSystemException('This card cannot be discarded');
    }

    // Discard the card
    ZooCards::insertAtBottom($cardId, 'scoringDeck');
    Notifications::discardScoringCard($player, ZooCards::getSingle($cardId));

    // Make the player inactive
    $game = Game::get();
    $game->gamestate->setPlayerNonMultiactive($player->getId(), 'next');
    if (count($game->gamestate->getActivePlayerList()) > 0) {
      return;
    }

    // No one is still active
    if (Globals::isEndTriggered() && Globals::getEndRemainingPlayers() == []) {
      // end of game, all cards are discarded
      $game->gamestate->jumpToState(\ST_PRE_END_OF_GAME);
    } else {
      // activate previous player & trigger engine
      $game->gamestate->changeActivePlayer($this->getCtxArg('current'));
      $this->resolveAction([], !Globals::isSolo());
    }
  }
}
