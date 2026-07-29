<?php
namespace AGR\Cards\A;

use AGR\Core\Engine;
use AGR\Managers\Meeples;
use AGR\Core\Notifications;

class A102_Grocer extends \AGR\Models\Occupation
{
  public function __construct($row)
  {
    parent::__construct($row);
    $this->id = 'A102_Grocer';
    $this->name = clienttranslate('Grocer');
    $this->deck = 'A';
    $this->number = 102;
    $this->category = GOODS_PROVIDER;
    $this->desc = [
      clienttranslate(
        'Pile the following goods on this card (<WOOD>, <GRAIN>, <REED>, <STONE>, <VEGETABLE>, <CLAY>, <REED>, <VEGETABLE>). At any time, you can buy the top good for 1 <FOOD>.'
      ),
    ];
    $this->players = '1+';
    $this->holder = true;
  }

  public function onBuy($player)
  {
    $types = [VEGETABLE, REED, CLAY, VEGETABLE, STONE, REED, GRAIN, WOOD];

    $created = [];
    foreach ($types as $i => $type) {
      $created = array_merge(
        $created,
        Meeples::createResourceInLocation($type, $this->id, $player->getId(), null, null, 1, $i)
      );
    }

    Notifications::accumulate(Meeples::getMany($created), true);
  }

  public function isListeningTo($event)
  {
    return $this->isAnytime($event) &&
      $this->getNextResource() != null &&
      !$this->hasPendingGoodsInEngine() &&
      $this->getPlayer()->countReserveResource(FOOD) >= 1;
  }

  private function hasPendingGoodsInEngine()
  {
    if (Engine::$tree === null) {
      return false;
    }

    $goodIds = array_column($this->getRemainingGoods(), 'id');
    if (empty($goodIds)) {
      return false;
    }

    $stack = [Engine::$tree];
    while (!empty($stack)) {
      $node = array_pop($stack);
      if ($node->isResolved()) {
        continue;
      }
      $args = $node->getArgs() ?? [];
      if (isset($args['meeple']) && in_array($args['meeple'], $goodIds)) {
        return true;
      }
      foreach ($node->getChilds() as $child) {
        $stack[] = $child;
      }
    }
    return false;
  }

  public function getRemainingGoods()
  {
    return array_reverse(Meeples::getResourcesOnCard($this->id, null, null)->toArray());
  }

  public function onPlayerAtAnytime($player, $event)
  {
    $goods = $this->getRemainingGoods();
    $max = min(count($goods), $this->getPlayer()->countReserveResource(FOOD));

    if ($max <= 1) {
      $meeple = $this->getNextResource();
      return [
        'type' => NODE_SEQ,
        'childs' => [
          $this->payNode([FOOD => 1]),
          $this->receiveNode($meeple['id'], true),
        ],
      ];
    }

    return [
      'action' => SPECIAL_EFFECT,
      'args' => [
        'cardId' => $this->id,
        'method' => 'buyGoods',
      ],
    ];
  }

  public function getBuyGoodsDescription()
  {
    return clienttranslate('Pay <FOOD>, buy goods');
  }

  public function argsBuyGoods()
  {
    $goods = array_map(function ($good) {
      return [
        'id' => $good['id'],
        'type' => $good['type'],
      ];
    }, $this->getRemainingGoods());

    return [
      'cardId' => $this->id,
      'goods' => $goods,
      'max' => min(count($goods), $this->getPlayer()->countReserveResource(FOOD)),
      'description' => clienttranslate('${actplayer} may buy the top goods from Grocer for 1 <FOOD> each'),
      'descriptionmyturn' => clienttranslate('Choose how many Grocer goods to buy'),
    ];
  }

  public function actBuyGoods($n)
  {
    $goods = $this->getRemainingGoods();
    $max = min(count($goods), $this->getPlayer()->countReserveResource(FOOD));

    if ($n < 1 || $n > $max) {
      throw new \BgaVisibleSystemException('Invalid Grocer quantity');
    }

    $childs = [$this->payNode([FOOD => $n])];
    foreach (array_slice($goods, 0, $n) as $good) {
      $childs[] = $this->receiveNode($good['id'], true);
    }

    Engine::insertAsChild([
      'type' => NODE_SEQ,
      'childs' => $childs,
    ]);
  }
}
