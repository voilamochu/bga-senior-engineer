<?php

namespace ARK\Actions\Effects;

use ARK\Managers\Players;
use ARK\Managers\ZooCards;
use ARK\Core\Notifications;
use ARK\Core\Globals;
use ARK\Core\Engine;
use ARK\Helpers\Utils;
use ARK\Helpers\FlowConvertor;
use ARK\Actions\Build;
use ARK\Models\Player;
use ARK\Core\Stats;

class Adapt extends \ARK\Models\Action
{
  public function getState(): int
  {
    return ST_ADAPT;
  }

  public function getDescription(): array
  {
    return [
      'log' => clienttranslate('Adapt ${n}'),
      'args' => [
        'n' => $this->getN(),
      ]
    ];
  }

  public function isIrreversible(?Player $player = null): bool
  {
    return true;
  }

  public function stPreAdapt()
  {
    $player = Players::getActive();
    $cards = ZooCards::draw($player, $this->getN(), 'scoringDeck', 'scoringHand');
    Notifications::preAdapt($player, $cards);
  }

  public function argsAdapt()
  {
    $player = Players::getActive();
    $cards = $player->getScoringHand();

    return [
      'n' => $this->getN(),
      '_private' => [
        'active' => [
          'cardIds' => $cards->getIds(),
        ],
      ],
    ];
  }


  public function actAdapt($cardIdsToDiscard)
  {
    $this->checkAction('actAdapt');

    $player = Players::getActive();
    $args = $this->getArgs();
    if (count(array_diff($cardIdsToDiscard, $args['_private']['active']['cardIds'])) > 0) {
      throw new \BgaVisibleSystemException('Invalid cards to discard. Should not happen');
    }

    $cardsToDiscard = ZooCards::getMany($cardIdsToDiscard);
    foreach ($cardIdsToDiscard as $cId) {
      ZooCards::insertAtBottom($cId, 'scoringDeck');
    }
    Notifications::adapt($player, $cardsToDiscard);
    $this->resolveAction([]);
  }
}
