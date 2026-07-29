<?php

namespace ARK\Actions;

use ARK\Core\Notifications;
use ARK\Models\Player;
use ARK\Managers\Players;
use ARK\Core\Engine;

class Animals2Hunter extends \ARK\Models\Action
{
  public function getState(): int
  {
    return ST_ANIMALS2_HUNTER;
  }

  public function getDescription(): array
  {
    $n = $this->getCtxArg('upgraded') ? 6 : 4;
    return [
      'log' => clienttranslate('Reveal no Animal: Hunter ${n}'),
      'args' => ['n' => $n],
    ];
  }

  public function stPreAnimals2Hunter()
  {
    // Ensure checkpoint
  }

  public function isOptional(): bool
  {
    return true;
  }

  public function isDoable(Player $player): bool
  {
    return $player->getHand(CARD_ANIMAL)->count() == 0;
  }

  public function isAutomatic(?Player $player = null): bool
  {
    return true;
  }

  public function isIrreversible(?Player $player = null): bool
  {
    return true;
  }

  public function stAnimals2Hunter()
  {
    $player = Players::getActive();
    $n = $this->getCtxArg('upgraded') ? 6 : 4;
    Notifications::message(clienttranslate('${player_name} has no animal left in hand and gains Hunter ${n} (Animals 2)'), [
      'player' => $player,
      'n' => $n,
    ]);

    $node = $this->ctx;
    $node->replace(Engine::buildTree([
      'action' => HUNTER,
      'args' => ['n' => $n]
    ]));
    $this->resolveAction([]);
  }
}
