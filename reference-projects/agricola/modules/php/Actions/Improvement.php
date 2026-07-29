<?php
namespace AGR\Actions;
use AGR\Managers\PlayerCards;
use AGR\Managers\Players;
use AGR\Helpers\Collection;
use AGR\Core\Stats;
use AGR\Helpers\Utils;

class Improvement extends \AGR\Models\Action
{
  public function __construct($row)
  {
    parent::__construct($row);
  }

  public function getState()
  {
    return ST_IMPROVEMENT;
  }

  public function getTypes($player = null)
  {
    $types = $this->getCtxArgs()['types'];
    if ((($player != null && $player->hasPlayedCard('C27_Blueprint')) ||
      ($player != null && $player->hasPlayedCard('D26_CarpentersYard')) ||
      ($player != null && $player->hasPlayedCard('D131_CraftsmanshipPromoter')) || 
      ($player != null && $player->hasPlayedCard('E91_PlowBuilder')) || 
      ($player != null && $player->hasPlayedCard('E109_BraidMaker')) || 
      ($player != null && $player->hasPlayedCard('E161_ElderBaker'))) &&
      !in_array(MAJOR, $this->getTypes()) &&
      $this->isTrueAction())
    {
      $types[] = MAJOR;
    }
    return $types;
  }

  public function getDescription($ignoreResources = false, $short = false)
  {
    $args = $this->getCtxArgs();
    $types = $this->getTypes();
    if (!Utils::hasReplace($this->getClassName()) || ($args['checkedReplaceAction'] ?? false)) {
      if ($types == [MAJOR]) {
        return $short ? clienttranslate('major') : clienttranslate('Build a major improvement');
      }
      if ($types == [MINOR]) {
        return $short ? clienttranslate('minor') : clienttranslate('Build a minor improvement');
      }
      if ($types == [MINOR, MAJOR]) {
        return $short ? clienttranslate('major/minor') : clienttranslate('Build a major or minor improvement');
      }
    } else {
      if ($types == [MAJOR]) {
        return $short ? clienttranslate('major') : clienttranslate('Build a major improvement / Replace action');
      }
      if ($types == [MINOR]) {
        return $short ? clienttranslate('minor') : clienttranslate('Build a minor improvement / Replace action');
      }
      if ($types == [MINOR, MAJOR]) {
        return $short ? clienttranslate('major/minor') : clienttranslate('Build a major or minor improvement / Replace action');
      }
    }
  }

  public function getAvailableCards()
  {
    $cards = new Collection();
    $types = $this->getTypes();
    foreach ($types as $type) {
      $cards = $cards->merge(PlayerCards::getAvailables($type));
    }
    return $cards;
  }

  public function getBuyableCards($player, $ignoreResources = false)
  {
    $args = [
      'actionCardId' => $this->ctx != null ? $this->ctx->getCardId() : null,
      'actionType' => $this->getCtxArgs()['actionType'] ?? null
    ];

    $customBuy = $this->getCtxArgs()['allowedPurchases'] ?? null;
    if (!is_null($customBuy)) {
      $buy = $this->getAvailableCards()->filter(function ($imp) use ($player, $ignoreResources, $args, $customBuy) {
        return $imp->isBuyable($player, $ignoreResources, $args) && in_array($imp->getId(), $customBuy);
      });
      return $buy;
    }

    $buy = $this->getAvailableCards()->filter(function ($imp) use ($player, $ignoreResources, $args) {
      return $imp->isBuyable($player, $ignoreResources, $args);
    });

    if ($player->hasPlayedCard('C27_Blueprint') && !in_array(MAJOR, $this->getTypes()) && $this->isTrueAction()) {
      $cardList = ['Major_Joinery', 'Major_Pottery', 'Major_Basket'];
      foreach ($cardList as $card) {
        if (PlayerCards::get($card)->isBuyable($player, $ignoreResources, $args)) {
          $buy[$card] = PlayerCards::get($card);
        }
      }
    }

    if ($player->hasPlayedCard('D26_CarpentersYard') && !in_array(MAJOR, $this->getTypes()) && $this->isTrueAction()) {
      $cardList = ['Major_Joinery', 'Major_Well'];
      foreach ($cardList as $card) {
        if (PlayerCards::get($card)->isBuyable($player, $ignoreResources, $args)) {
          $buy[$card] = PlayerCards::get($card);
        }
      }
    }

    if ($player->hasPlayedCard('D131_CraftsmanshipPromoter') && !in_array(MAJOR, $this->getTypes()) && $this->isTrueAction()) {
      $cardList = ['Major_ClayOven', 'Major_StoneOven', 'Major_Joinery', 'Major_Pottery', 'Major_Basket'];
      foreach ($cardList as $card) {
        if (PlayerCards::get($card)->isBuyable($player, $ignoreResources, $args)) {
          $buy[$card] = PlayerCards::get($card);
        }
      }
    }

    if ($player->hasPlayedCard('E91_PlowBuilder') && !in_array(MAJOR, $this->getTypes()) && $this->isTrueAction()) {
      $cardList = ['Major_Joinery'];
      foreach ($cardList as $card) {
        if (PlayerCards::get($card)->isBuyable($player, $ignoreResources, $args)) {
          $buy[$card] = PlayerCards::get($card);
        }
      }
    }

    if ($player->hasPlayedCard('E109_BraidMaker') && !in_array(MAJOR, $this->getTypes()) && $this->isTrueAction()) {
      $cardList = ['Major_Basket'];
      foreach ($cardList as $card) {
        if (PlayerCards::get($card)->isBuyable($player, $ignoreResources, $args)) {
          $buy[$card] = PlayerCards::get($card);
        }
      }
    }

    if ($player->hasPlayedCard('E161_ElderBaker') && !in_array(MAJOR, $this->getTypes()) && $this->isTrueAction()) {
      $cardList = ['Major_StoneOven'];
      foreach ($cardList as $card) {
        if (PlayerCards::get($card)->isBuyable($player, $ignoreResources, $args)) {
          $buy[$card] = PlayerCards::get($card);
        }
      }
    }

    // Check for special conditions: A23_StoneCompany
    $purchaseCondition = $this->getCtxArgs()['purchaseCondition'] ?? null;
    if (!is_null($purchaseCondition)) {
      if ($purchaseCondition == 'A23_StoneCompany') {
        $buy = $buy->filter(function ($imp) use ($player, $ignoreResources, $args) {
          $costs = $imp->getCosts($player, $args)['trades'];
          foreach ($costs as $cost) {
            foreach ($cost as $res => $amt) {
              if ($res == STONE && $amt >= 1) return true;
            }
          }
          return false;
        });
      }
    }
    return $buy;
  }

  public function isDoable($player, $ignoreResources = false)
  {
    if ($this->isFree()) {
      $ignoreResources = true;
    }

    return !$this->getBuyableCards($player, $ignoreResources)->empty();
  }

  public function isTrueAction()
  {
    return $this->getCtxArgs()['trueAction'] ?? true;
  }

  public function isFree()
  {
    return $this->getCtxArgs()['free'] ?? false;
  }

  public function argsImprovement()
  {
    $player = Players::getActive();

    return [
      'i18n' => ['strTypes'],
      'strTypes' => $this->getDescription(false, true),
      'types' => $this->getTypes($player),
      '_private' => [
        'active' => [
          'cards' => $this->getBuyableCards($player)->getIds(),
        ],
      ],
    ];
  }

  public function actImprovement($cardId)
  {
    self::checkAction('actImprovement');
    $player = Players::getActive();
    // Sanity check on card
    $cards = $this->getBuyableCards($player);
    if (!$cards->offsetExists($cardId)) {
      throw new \BgaVisibleSystemException('You can\'t play this improvement');
    }

    $card = $cards[$cardId];
    $eventData = [
      'cardId' => $cardId,
      'types' => $this->getTypes(),
      'actionCardId' => $this->ctx != null ? $this->ctx->getCardId() : null,
      'actionType' => $this->getCtxArgs()['actionType'] ?? null,
      'trueAction' => $this->isTrueAction(),
    ];
    $card->actBuy($player, $eventData);
    if ($card->getType() == MAJOR) {
      Stats::incTotalMajorBuilt($player);
    } else {
      Stats::incTotalMinorBuilt($player);
    }

    $this->checkAfterListeners($player, $eventData);
    $this->resolveAction(['cardId' => $cardId]);
  }
}
