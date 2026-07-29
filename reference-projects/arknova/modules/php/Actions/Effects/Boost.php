<?php

namespace ARK\Actions\Effects;

use ARK\Managers\Players;
use ARK\Managers\ZooCards;
use ARK\Core\Notifications;
use ARK\Core\Engine;
use ARK\Managers\ActionCards;
use ARK\Models\ZooCard;
use ARK\Models\Player;

class Boost extends \ARK\Models\Action
{
  public function getState(): int
  {
    return ST_BOOST;
  }

  public function isOptional(): bool
  {
    return true;
  }

  public function getDescription(): string|array
  {
    return [
      'log' => clienttranslate('Move ${card_type} action card to <STRENGTH:1> or <STRENGTH:5>'),
      'args' => [
        'i18n' => ['card_type'],
        'card_type' => $this->getN(), // already wrapped in clienttranslate in action cards classes
      ],
    ];
  }

  public function argsBoost()
  {
    $player = Players::getActive();
    $type = $this->getN();

    return [
      'i18n' => ['card_type'],
      'card_type' => $type,
    ];
  }

  public function actBoost($position)
  {
    $this->checkAction('actBoost');
    if ($position != 1 && $position != 5) {
      throw new \BgaVisibleSystemException('Not valid position. Should not happen');
    }

    $player = Players::getActive();
    $type = $this->getN();
    $oCard = $player->getActionCardOfType($type);
    $actionCards = $player->moveActionCard($type, $position);
    Notifications::boost($player, $oCard, $position, $actionCards);
    $this->resolveAction([$position]);
  }
}
