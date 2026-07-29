<?php
namespace AGR\Actions;
use AGR\Core\Globals;
use AGR\Core\Stats;
use AGR\Helpers\Utils;
use AGR\Managers\PlayerCards;
use AGR\Managers\Players;

class Occupation extends \AGR\Models\Action
{
  public function __construct($row)
  {
    parent::__construct($row);
  }

  public function getState()
  {
    return ST_OCCUPATION;
  }

  public function getDescription($ignoreResources = false)
  {
    return clienttranslate('Play an occupation');
  }

  public function getAvailableCards()
  {
    return PlayerCards::getAvailables(OCCUPATION);
  }

  public function getBuyableCards($player, $ignoreResources = false)
  {
    $allowedCards = $this->ctx->getArgs()['allowedCards'] ?? null;

    $args = [
      'actionCardId' => $this->ctx != null ? $this->ctx->getCardId() : null,
    ];

    if (!is_null($allowedCards)) {
      return $this->getAvailableCards()->filter(function ($occ) use ($allowedCards) {
        return in_array($occ->getId(), $allowedCards);
      });
    }

    return $this->getAvailableCards()->filter(function ($occ) use ($player, $ignoreResources, $args) {
      return $occ->isBuyable($player, $ignoreResources, $args);
    });
  }

  public function isDoable($player, $ignoreResources = false) {
    $buyableCards = $this->getBuyableCards($player, $ignoreResources);
    if ($buyableCards->empty()) {
      return false;
    }
    if ($ignoreResources) {
      return true;
    }
    $costs = $this->getCosts($player);
    $canBuy = $player->canBuy($costs);
    return  $canBuy;
  }

  public function getCosts($player)
  {
    if (!isset($this->getCtxArgs()['cost'])) {
      return [];
    }
    $cost = $this->getCtxArgs()['cost'];
    $food = $cost[FOOD] ?? 0;
    $costs = Utils::formatCost([FOOD => $food]);
    $this->checkCostModifiers($costs, $player, ['actionCardId' => $this->getCtxArgs()['actionCardId'] ?? null]);
    return $costs;
  }

  public function stOccupation()
  {
    $player = Players::getActive();
    $canPay =  $player->canBuy($this->getCosts($player));
    if (!$canPay) {
      throw new \BgaVisibleSystemException('You can\'t afford occupation'); // TODO : translate
    }
  }

  public function argsOccupation()
  {
    $player = Players::getActive();

    return [
      'strTypes' => $this->getDescription(true),
      'types' => [OCCUPATION],
      '_private' => [
        'active' => [
          'cards' => $this->getBuyableCards($player)->getIds(),
        ],
      ],
    ];
  }

  public function actOccupation($cardId)
  {
    if(array_key_exists('setTurnId',$this->getCtxArgs())){
      $lessonsTurnId = $this->getCtxArgs()['setTurnId'];
      Globals::setLessonsTurnId($lessonsTurnId);
    }
    self::checkAction('actOccupation');
    $player = Players::getActive();
    // Sanity check on card
    $cards = $this->getBuyableCards($player);
    if (!$cards->offsetExists($cardId)) {
      throw new \BgaVisibleSystemException('You can\'t play this occupation');
    }

    $card = $cards[$cardId];
    $args = $this->getCtxArgs();
    $paymentSourceInfo = [
      'sourceAction' => 'Occupation',
      'cardId' => $cardId
    ];
    $costs = $this->getCosts($player);
    $player->pay(1, $costs, $args['source'] ?? clienttranslate('Lessons'), true, $paymentSourceInfo);

    $card->actBuy($player);
    Stats::incTotalOccupationBuilt($player);

    $attributionCardId = $args['cardId'] ?? ($this->ctx != null ? $this->ctx->getCardId() : null);
    if (!is_null($attributionCardId)) {
      $attrCards = PlayerCards::getMany([$attributionCardId], false);
      if ($attrCards->count() === 1) {
        $attrCard = $attrCards->first();
        if ($attrCard->getPId() === $player->getId()) {
          $attrCard->incStats('gain', OCCUPATION, 1);
        }
      }
    }

    $eventData = ['cardId' => $cardId, 'costs' => $costs];
    $this->checkAfterListeners($player, $eventData);
    $this->resolveAction(['cardId' => $cardId]);
  }
}
