<?php

namespace ARK\Actions\Effects;

use ARK\Managers\Players;
use ARK\Managers\ZooCards;
use ARK\Core\Notifications;
use ARK\Core\Globals;
use ARK\Managers\Meeples;
use ARK\Models\Player;

class Multiplier extends \ARK\Models\Action
{
  public function getState(): int
  {
    return ST_MULTIPLIER;
  }

  public function getDescription(): string|array
  {
    if ($this->getN() == 'all') {
      return clienttranslate('Place a multiplier token');
    } else {
      return [
        'log' => clienttranslate('Place a multiplier token on ${n}'),
        'args' => [
          'i18n' => ['n'],
          'n' => $this->getN(),
        ],
      ];
    }
  }

  public function stMultiplier()
  {
    if ($this->getN() != 'all') {
      $this->actMultiplier($this->getN(), true);
    }
  }

  public function argsMultiplier()
  {
    $player = Players::getActive();
    return ['cards' => $player->getActionCards()];
  }

  public function actMultiplier($type, $bypass = false)
  {
    self::checkAction('actMultiplier', $bypass);
    $player = Players::getActive();
    $card = $player->getActionCardOfType($type, true);
    $meeple = Meeples::addOnActionCard(MULTIPLIER, $card->getId(), $player->getId(), $this->getN() == 'all' ? ACTIVE : INACTIVE);
    Notifications::multiplier($player, $card, $meeple);
    $this->resolveAction([]);
  }
}
