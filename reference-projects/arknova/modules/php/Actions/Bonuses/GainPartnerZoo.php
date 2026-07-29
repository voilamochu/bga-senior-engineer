<?php

namespace ARK\Actions\Bonuses;

use ARK\Managers\Meeples;
use ARK\Managers\Players;
use ARK\Managers\ZooCards;
use ARK\Core\Notifications;
use ARK\Core\Stats;
use ARK\Helpers\Utils;
use ARK\Actions\Association;
use ARK\Models\Player;

class GainPartnerZoo extends \ARK\Models\Action
{
  public function getState(): int
  {
    return ST_GAIN_PARTNER_ZOO;
  }

  public function getDescription(): string|array
  {
    return clienttranslate('Gain a partner zoo');
  }

  public function isOptional(): bool
  {
    $player = Players::getActive();
    return !$this->isDoable($player);
  }

  public function isDoable(Player $player): bool
  {
    return !empty($this->getAvailablePartnerZoos($player));
  }

  public function getAvailablePartnerZoos(Player $player)
  {
    $actionCard = $player->getActionCardOfType('Association');
    return Association::getAvailablePartnerZoosAux($player, $actionCard->getLevel());
  }

  public function argsGainPartnerZoo()
  {
    $player = Players::getActive();
    return [
      'meeples' => $this->getAvailablePartnerZoos($player),
    ];
  }

  public function actGainPartnerZoo($meepleId)
  {
    // Sanity checks
    self::checkAction('actGainPartnerZoo');
    $player = Players::getActive();
    $meepleIds = $this->getAvailablePartnerZoos($player);
    if (!in_array($meepleId, $meepleIds)) {
      throw new \BgaVisibleSystemException('Cannot take that zoo partner. Should not happen');
    }

    // Add the zoo
    $bonuses = $player->addPartnerZoo($meepleId);
    $this->insertBonusesFlow($bonuses, \clienttranslate('partner zoo'));
    $this->resolveAction(['meepleId' => $meepleId]);
  }
}
