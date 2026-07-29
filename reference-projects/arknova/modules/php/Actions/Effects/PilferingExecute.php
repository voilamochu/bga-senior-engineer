<?php

namespace ARK\Actions\Effects;

use ARK\Managers\Players;
use ARK\Helpers\Utils;
use ARK\Core\Notifications;
use ARK\Core\Engine;
use ARK\Managers\Meeples;
use ARK\Models\Player;

class PilferingExecute extends \ARK\Models\Action
{
  public function getState(): int
  {
    return ST_PILFERING_EXECUTE;
  }

  public function argsPilferingExecute()
  {
    $player = Players::getActive();

    $choices = [];
    $n = null;
    if ($player->getMoney() >= 5) {
      $n = 5;
      $choices[] = 'money';
    }

    if ($player->getHand()->count() != 0) {
      $choices[] = 'cards';
    }

    if (empty($choices)) {
      $n = $player->getMoney();
      $choices[] = 'money';
    }

    return [
      'player_name' => Players::get($this->getCtxArg('pId'))->getName(),
      'possibleChoices' => $choices,
      'descSuffix' => is_null($n) ? 'nomoney' : '',
      'n' => $n ?? 0,
      'canUnstore' => $player->getStoredCards()->count() > 0,
    ];
  }

  public function stPilferingExecute()
  {
    $args = $this->argsPilferingExecute();
    if (count($args['possibleChoices']) == 1 && !$args['canUnstore']) {
      $this->actPilferingExecute($args['possibleChoices'][0], true);
    }
  }

  public function actPilferingExecute($choice, $auto = false)
  {
    self::checkAction('actPilferingExecute', $auto);
    $player = Players::getActive();
    if ($auto) {
      Notifications::message(clienttranslate('Pilfering effect automatically resolved as only one possibility'));
    }

    $args = $this->argsPilferingExecute();
    if (!in_array($choice, $args['possibleChoices'])) {
      throw new \BgaVisibleSystemException('You cannot select this choice. Should not happen');
    }

    $otherPlayer = Players::get($this->getCtxArg('pId'));
    if ($choice == 'money') {
      $amount = min($player->getMoney(), 5);
      $player->pay($amount, false);
      $otherPlayer->incMoney($amount, false);
      Notifications::pilferingMoney($player, $amount, $otherPlayer);
    } else {
      $card = $player->getHand()->rand();
      $card->setPId($otherPlayer->getId());
      Notifications::pilferingCard($player, $card, $otherPlayer);
    }

    $this->resolveAction([$choice], $choice == 'cards');
  }
}
