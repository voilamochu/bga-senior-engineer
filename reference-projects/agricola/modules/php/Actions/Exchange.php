<?php
namespace AGR\Actions;
use AGR\Core\Globals;
use AGR\Core\Notifications;
use AGR\Core\Stats;
use AGR\Core\Engine;
use AGR\Helpers\UserException;
use AGR\Helpers\Utils;
use AGR\Managers\Players;
use AGR\Managers\PlayerCards;
use AGR\Models\Action;

class Exchange extends Action
{
  public function getState()
  {
    return ST_EXCHANGE;
  }

  public function getDescription($ignoreResources = false)
  {
    $args = $this->getCtxArgs();
    $trigger = $args['trigger'] ?? ANYTIME;
    if ($trigger == BREAD) {
      if (!Utils::hasReplace($this->getClassName()) || ($args['checkedReplaceAction'] ?? false)) {
        return clienttranslate('Bake bread');
      } else {
        return clienttranslate('Bake bread / Replace bake bread');
      }
    } else {
      return clienttranslate('Cook / Exchange resources');
    }
  }

  /**
   * Allow for args as $ctx instead of a tree node => useful for harvest
   */
  public function getCtxArgs()
  {
    return $this->ctx == null ? null : (is_array($this->ctx) ? $this->ctx : $this->ctx->getArgs());
  }

  public function getTrigger()
  {
    return $this->getCtxArgs()['trigger'] ?? ($this->isHarvest() ? HARVEST : ANYTIME);
  }

  public function getSpecialSource()
  {
    return $this->getCtxArgs()['specialSource'] ?? '';
  }

  public function getExchanges($player)
  {
    $args = $this->getCtxArgs();
    $trigger = $this->getTrigger();
    $exclusive = $args['exclusive'] ?? $trigger == BREAD;
    return $args['exchanges'] ?? $player->getPossibleExchanges($trigger, $exclusive);
  }

  public function isDoable($player, $ignoreResources = false)
  {
    if ($this->getTrigger() == 'huntingTrophy') {
      return true;
    }

    $args = $this->getCtxArgs();

    $trigger = $this->getTrigger();
    $exclusive = $args['exclusive'] ?? $trigger == BREAD;
    if (!$exclusive){
      return true;
    }

    $exchanges = $this->getExchanges($player);
    $resources = $player->getExchangeResources();

    // Try to find one doable exchange
    foreach ($exchanges as $exchange) {
      $ok = true;
      foreach ($exchange['from'] as $type => $amount) {
        $ok = $ok && $resources[$type] >= $amount;
      }

      if ($ok || $ignoreResources) {
        return true;
      }
    }
    return false;
  }

  public function isOptional()
  {
    if ($this->getTrigger() == 'huntingTrophy') {
      return false;
    }

    return !$this->isMandatory();
  }

  public function isMandatory()
  {
    $args = $this->getCtxArgs();
    if ($args['mustCook'] ?? false) {
      return true;
    }
    if (!is_object($this->ctx) || $this->getTrigger() != BREAD) {
      return false;
    }
    // Enforced at node level: makeBakeMandatory, or explicitly chosen from a menu
    if ($this->ctx->isMandatory()) {
      return true;
    }
    // An action space's own bake is committed once entered: a space use may never end with
    // no action taken. Unless a sibling action already resolved (and/or spaces).
    if (!Utils::isActionSpaceId($this->ctx->getCardId())) {
      return false;
    }
    $node = $this->ctx->getParent();
    while ($node != null && $node->getType() != NODE_OR) {
      $node = $node->getParent();
    }
    return $node == null || !$node->areChildrenOptional();
  }

  // Needed for edge case: Small Potter's Oven lets you build oven before baking. If player bakes grain
  // during an optional bake, then hits a mandatory bake with no grain left, they get stuck see bug 223552
  public function isAlreadySatisfied()
  {
    if ($this->getTrigger() != BREAD) {
      return false;
    }

    foreach (Engine::getResolvedActions([EXCHANGE]) as $node) {
      $resArgs = $node->getActionResolutionArgs() ?? [];
      if (($node->getArgs()['trigger'] ?? null) == BREAD && !empty($resArgs['trades'])) {
        return true;
      }
    }
    return false;
  }

  public function isAutomatic($player = null)
  {
    // A bake always needs the player to pick trades
    if ($this->getTrigger() == BREAD) {
      return false;
    }

    $args = $this->argsExchange();
    if (!$this->isMandatory()) {
      return false;
    }

    $exchanges = $this->getAnimalExchanges();
    foreach ($args['extraAnimals'] as $type => $amount) {
      if ($amount > 0 && count($exchanges[$type]) != 1) {
        return false;
      }
    }

    return true;
  }

  public static function isCookingSource($source)
  {
    // Cookery Extension appends " (x2)" to the cookery name
    $source = preg_replace('/\s*\(x2\)\s*$/', '', $source);
    
    return $source == 'Fireplace' ||
      $source == 'Cooking Hearth' ||
      $source == 'Oriental Fireplace' ||
      $source == 'Earth Oven';
  }

  public static function isBread($args)
  {
    return ($args['trigger'] ?? '') == BREAD;
  }

  /*
   * FORMAT DOCUMENTATION
   * $exchanges is an array of $exchange
   *
   * $exchange : [
   *    'source' => (optional) translate string for UI
   *    'triggers' => null if at any time, of array of triggers such as BREAD, HARVEST
   *    'max' => maxAmount of time you can use this exchange
   *    'from' => [
   *        resourceType => amount,
   *          ....
   *    ],
   *    'to' => [
   *        resourceType => amount,
   *        ...
   *    ]
   * ]
   */

  public function argsExchange()
  {
    $player = Players::getActive();
    $trigger = $this->getTrigger();
    $exchanges = $this->getExchanges($player);
    $suffixes = [
      BREAD => 'bread',
    ];
    $mustCook = $this->getCtxArgs()['mustCook'] ?? false;

    return [
      'exchanges' => $exchanges,
      'resources' => $player->getExchangeResources(),
      'trigger' => $trigger,
      'descSuffix' => $suffixes[$trigger] ?? '',
      'canGoToExchange' => false,
      'extraAnimals' => $player->countAnimalsInReserve(),
      'mandatory' => $this->isMandatory(),
    ];
  }

  public function getAnimalExchanges()
  {
    $args = $this->argsExchange();
    $res = [];
    foreach ($args['extraAnimals'] as $type => $amount) {
      if ($amount == 0) {
        continue;
      }

      $exchanges = array_filter($args['exchanges'], function ($exchange) use ($type, $amount) {
        return array_keys($exchange['from']) == [$type] && $exchange['max'] >= $amount;
      });
      $res[$type] = $exchanges;
    }

    return $res;
  }

  public function stExchange()
  {
    if (!$this->isAutomatic()) {
      return;
    }

    $args = $this->argsExchange();
    $exchanges = $this->getAnimalExchanges();
    $trades = [];
    foreach ($args['extraAnimals'] as $type => $amount) {
      for ($i = 0; $i < $amount; $i++) {
        $trades[] = array_keys($exchanges[$type])[0];
      }
    }

    $this->actExchange($trades);
  }

  public function actExchange($trades)
  {
    $this->checkAction('actExchange');
    $args = $this->argsExchange();
    if ($args['trigger'] == BREAD && empty($trades) && $this->isMandatory()) {
      throw new UserException(clienttranslate('You must exchange at least 1 grain in the bake bread action'));
    }
    $isHarvest = Globals::isHarvest() && !Globals::isFieldPhase();
    $player = Players::getActive();
    $exchanges = $args['exchanges'];
    $reserve = $args['resources'];
    $nbExchanges = array_map(function ($a) {
      return 0;
    }, $exchanges);
    $discarded = [];
    $deleted = [];
    $created = [];
    $sourceId = is_object($this->ctx) ? $this->ctx->getSourceId() : null;
    $eventData = [
      'trigger' => $this->getTrigger(),
      'exchanges' => $exchanges,
      'trades' => $trades,
      'created' => $created,
    ];

    $usedCookery = false;
    for ($i = 0; $i < count($trades); $i++) {
      $tradeIndex = $trades[$i];
      $stat = [];
      $discard = ($tradeIndex['source'] ?? null) == 'discard';

      // process discarded goods
      if ($discard) {
        foreach ($tradeIndex['from'] as $res => $amount) {
          if ($player->hasPlayedCard('E124_MayorCandidate') && ($res == WOOD || $res == STONE)) {
            throw new \BgaUserException('You are not allowed to discard wood or stone (Mayor Candidate)');
          }

          $reserve[$res] -= $amount;
          if ($reserve[$res] < 0) {
            throw new \BgaUserException('Using too many resources. Should not happen');
          }
          $stat[$res] = $amount;
          array_push($discarded, ...$player->useResource($res, $amount));
        }
      }

      else {
        // check exchange is authorized and exist
        $exchange = $exchanges[$tradeIndex] ?? null;

        if ($exchange == null) {
          throw new \BgaUserException('Exchange not possible. Should not happen');
        }

        // Check that trade is not executed too much
        $nbExchanges[$tradeIndex]++;
        if ($nbExchanges[$tradeIndex] > $exchange['max']) {
          throw new \BgaUserException('Too many exchanges of type. Should not happen');
        }

        // Check the player can afford exchange
        foreach ($exchange['from'] as $res => $amount) {
          $reserve[$res] -= $amount;
          if ($reserve[$res] < 0) {
            throw new \BgaUserException('Using too many resources. Should not happen');
          }
          $stat[$res] = $amount;
          array_push($deleted, ...$player->useResource($res, $amount));
        }

        // Create new resources
        foreach ($exchange['to'] as $res => $amount) {
          if ($res == SCORE) {
            $card = PlayerCards::get($exchange['scoreCardId']);
            $card->incBonusScore($amount);
            for ($j = 0; $j < $amount; $j++) {
              $created[] = ['type' => SCORE, 'location' => $exchange['scoreCardId'], 'pId' => $player->getId(), 'destroy' => true];
            }
            continue;
          }

          $reserve[$res] += $amount;
          array_push($created, ...$player->createResourceInReserve($res, $amount));

          $statCardId = $exchange['cardId'] ?? null;
          if (!is_null($statCardId)) {
            $statCards = PlayerCards::getMany([$statCardId], false);
            if ($statCards->count() === 1) {
              $statCard = $statCards->first();
              if ($statCard->getPId() === $player->getId()) {
                $statCard->incStats('gain', $res, $amount);
              }
            }
          }

          if ($res == FOOD) {
            foreach ($stat as $key => $value) {
              $f = 'incConverted' . \ucfirst($key);
              Stats::$f($player, $value);

              $conv = 'inc' . \ucfirst($key) . 'ToFood';
              Stats::$conv($player, $amount);
            }
          }
        }

        // Flag if needed
        if (\array_key_exists('flag', $exchange) && $exchange['flag'] != null) {
          $flags = Globals::getExchangeFlags();
          $flags[] = $exchange['flag'];
          Globals::setExchangeFlags($flags);

          $card = PlayerCards::get($exchange['flag']);
          if ($sourceId !== $card->getId()) {
            $card->incStats('used');
          }
          $card->setMarked(false);
        }
      }

      // Notify (group consecutive exchanges from the same source)
      $currentSource = $discard ? 'discard' : ($exchange['source'] ?? null);
      $nextTrade = $trades[$i + 1] ?? null;
      $nextSource = $nextTrade === null ? null
        : (is_array($nextTrade) ? ($nextTrade['source'] ?? null) : ($exchanges[$nextTrade]['source'] ?? null));
      if ($i == count($trades) - 1 || $nextSource !== $currentSource) {
        if (count($deleted) != 0 || count($created) != 0) {
          Notifications::exchange($player, $deleted, $created, $exchange['source']);
        }
        array_push($eventData['created'], ...$created);
        if ($player->hasInHand('A53_Claypipe') && Globals::isWorkPhase()) {
          $player->updateObtainedResources($created);
        }
        $deleted = [];
        $created = [];
      }

      // Set global variable if cooking equipment was used
      $source = $exchange['source'] ?? null;
      if ($source && self::isCookingSource($source)) {
        $cookedTurnId = Globals::getCookedTurnId();
        $cookedTurnId[$player->getId()] = Globals::getTurnId();
        Globals::setCookedTurnId($cookedTurnId);
      }
    }

    // Notify (discard)
    if (count($discarded) != 0) {
      Notifications::discard($player, $discarded);
    }

    if ($this->isMandatory()) {
      // If it was mandatory, discard excess animals
      $animals = $player->getAllReserveResources();
      foreach ([SHEEP, PIG, CATTLE] as $type) {
        if ($animals[$type] != 0) {
          Notifications::discardAnimals($player, $player->useResource($type, $animals[$type]));
        }
      }
    } else {
      // If we have traded an animal we need to reorganizes
      if (!$isHarvest) {
      $player->checkAnimalsInReserve();
      }
    }

    // hacky workaround for E62
    if ($this->getSpecialSource() != '') {
      $card = PlayerCards::get($this->getSpecialSource());
      $flow = $card->flagCardNode();
      Engine::insertAsChild($flow);
    }
    $this->checkListeners('Exchange', $player, $eventData);
    Notifications::updateDropZones($player);
    
    if (!$isHarvest) {
    $player->forceReorganizeIfNeeded();
    }
    
    $this->checkAfterListeners($player, $eventData);
    $this->resolveAction(['trades' => $trades]);

    // Harvest specific: after the exchange, if animals are in reserve, 
    // force a harvest reorganise at the root. Fixes Sheep Walker/Stable Yard crashing bug when reorganise
    // added to engine queue via checkAnimalsInReserve().
    if ($isHarvest) {
      $meeples = $player->getAnimals('reserve')->toArray();

      if (!empty($meeples)) {
        $needConfirm = $player->checkAutoReorganize($meeples);
        Notifications::moveAnimalsAround($player, $meeples);

        // After auto reorganise, decide whether we still need a manual step
        $stillInReserve = $player->getAnimals('reserve')->count() > 0;

        if ($needConfirm || $stillInReserve) {
          Engine::insertAtRoot([
            'pId'    => $player->getId(),
            'action' => REORGANIZE,
            'args'   => [
              'trigger' => HARVEST,
            ],
          ], false);

          Engine::proceed();
        }
      }
    }
  }
}
