<?php

namespace ARK\Actions\Effects;

use ARK\Managers\Players;
use ARK\Managers\ZooCards;
use ARK\Core\Notifications;
use ARK\Core\Globals;
use ARK\Core\Engine;
use ARK\Helpers\Utils;
use ARK\Helpers\FlowConvertor;
use ARK\Models\Player;

class Dominance extends \ARK\Models\Action
{
  public function getState(): int
  {
    return ST_DOMINANCE;
  }

  public function getDescription(): string|array
  {
    return clienttranslate('Add __Primates__ conservation project to hand');
  }

  public function isOptional(): bool
  {
    return true;
  }

  public function isAutomatic(?Player $player = null): bool
  {
    return true;
  }

  public function isIrreversible(?Player $player = null): bool
  {
    $player = $player ?? Players::getActive();
    $pIds = Globals::getEffectAssertion();
    return !in_array($player->getId(), $pIds) && count($pIds) > 0;
  }

  public function stPreDominance()
  {
    // JUST FOR THE CHECKPOINT IF NEEDED
  }


  public function stDominance()
  {
    $player = Players::getActive();

    $card = ZooCards::get('P108_Primates');
    if (in_array($card->getLocation(), ['projectDeck', 'previousBase'])) {
      $card->setLocation('hand');
      $card->setPId($player->getId());
      Notifications::dominance($player, $card);
    } else {
      Notifications::message(clienttranslate('${player_name} can\'t take __Primates__ conservation project because it\'s not in the deck.'), ['player' => $player]);
    }

    $this->resolveAction([]);
  }
}
