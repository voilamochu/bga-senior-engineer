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

class Symbiosis extends \ARK\Models\Action
{
  public function getState(): int
  {
    return ST_SYMBIOSIS;
  }

  public function getDescription(): string
  {
    return clienttranslate('Symbiosis: copy another ability');
  }

  public function isOptional(): bool
  {
    return !$this->isDoable(Players::getActive());
  }

  public function isDoable(Player $player): bool
  {
    return !empty($this->getEffects($player));
  }

  public function getTargetCards($player)
  {
    $source = $this->ctx->getSourceId();
    return $player->getPlayedCards(CARD_ANIMAL)->filter(function ($card) use ($source) {
      return $card->getId() != $source && in_array(SEA_ANIMAL, $card->getCategories())
        && $card->getLocation() != 'rescueStation'; // MAP 10
    });
  }

  public function getEffects($player)
  {
    $cards = $this->getTargetCards($player);
    $effects = [];
    foreach ($cards as $cId => $c) {
      // Prevent infinite loop when only animal is one with Symbiosis...
      $effect = Globals::isPeaceful() ? $c->getSoloAbility() : $c->getAbility();
      unset($effect[SYMBIOSIS]);
      if (!is_null($effect) && !empty($effect)) {
        $effects[$cId] = $effect;
      }
    }
    return $effects;
  }

  public function stSymbiosis()
  {
    $player = Players::getActive();
    $effects = $this->argsSymbiosis()['effects'];
    if (count($effects) == 1 && count($effects[array_key_first($effects)]) == 1) {
      $this->actSymbiosis(array_key_first($effects), array_key_first($effects[array_key_first($effects)]), true);
    }
  }

  public function argsSymbiosis()
  {
    $player = Players::getActive();

    return [
      'effects' => $this->getEffects($player)
    ];
  }

  public function actSymbiosis($cardId, $effectName, $auto = false)
  {
    if (!$auto) {
      self::checkAction('actSymbiosis');
    }

    $player = Players::getActive();
    $args = $this->argsSymbiosis();
    if (!in_array($cardId, array_keys($args['effects']))) {
      throw new \BgaVisibleSystemException('This card cannot be targeted. Should not happen ' . $cardId);
    }
    if (!array_key_exists($effectName, $args['effects'][$cardId])) {
      throw new \BgaVisibleSystemException('Wrong effect. Should not happen ' . $effectName);
    }

    $n = $args['effects'][$cardId][$effectName];
    $source = $this->ctx->getSourceId();

    $after =  [];
    $immediate = [];
    if (in_array($effectName, ['Clever', 'Boost', 'Action', 'Determination', 'Mark'])) {
      $after[] = [
        'action' => $effectName,
        'args' => ['n' => $n],
        'sourceId' => $source,
      ];
    } elseif ($effectName != 'FlockAnimal') {
      $immediate[] = [
        'action' => $effectName,
        'args' => ['n' => $n],
        'sourceId' => $source,
      ];
    }

    $this->pushParallelChilds($immediate);
    $this->pushAfterFinishingChilds($after);
    Notifications::message(clienttranslate('${player_name} selects ${card_name} to gain one effect (Symbiosis effect)'), ['player' => $player, 'card' => ZooCards::get($cardId)]);

    $this->resolveAction([]);
  }
}
