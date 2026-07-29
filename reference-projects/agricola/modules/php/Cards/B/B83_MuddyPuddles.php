<?php
namespace AGR\Cards\B;
use AGR\Core\Engine;
use AGR\Managers\Meeples;
use AGR\Core\Notifications;
use AGR\Core\Stats;

class B83_MuddyPuddles extends \AGR\Models\MinorImprovement
{
  public function __construct($row)
  {
    parent::__construct($row);
    $this->id = 'B83_MuddyPuddles';
    $this->name = clienttranslate('Muddy Puddles');
    $this->deck = 'B';
    $this->number = 83;
    $this->category = LIVESTOCK_PROVIDER;
    $this->desc = [
      clienttranslate(
        'Pile (from bottom to top) 1 <PIG>, 1 <FOOD>, 1 <CATTLE>, 1 <FOOD>, and 1 <SHEEP> on this card. At any time, you can pay 1 <CLAY> to take the top good.'
      ),
    ];
    $this->cost = [
      CLAY => 2,
    ];
    $this->holder = true;
    $this->isArtifexOrBubulcus = true;
  }
  
  public function onBuy($player)
  {
    $types = [PIG, FOOD, CATTLE, FOOD, SHEEP];

    foreach ($types as $i => $type) {
      Meeples::createResourceInLocation($type, $this->id, $player->getId(), null, null, 1, $i);
    }

    Notifications::accumulate(Meeples::getResourcesOnCard($this->id, $player->getId()), true);
  }
  
  public function isListeningTo($event)
  {
    return $this->isAnytime($event) &&
      $this->getNextResource() != null &&
      !$this->hasPendingGoodsInEngine() &&
      $this->getPlayer()->countReserveResource(CLAY) >= 1;
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
    $max = min(count($goods), $this->getPlayer()->countReserveResource(CLAY));

    if ($max <= 1) {
      $meeple = $this->getNextResource();
      return [
        'type' => NODE_SEQ,
        'childs' => [
          $this->payNode([CLAY => 1]),
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
    return clienttranslate('Pay <CLAY>, buy goods');
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
      'max' => min(count($goods), $this->getPlayer()->countReserveResource(CLAY)),
      'description' => clienttranslate('${actplayer} may buy the top goods from Muddy Puddles for 1 <CLAY> each'),
      'descriptionmyturn' => clienttranslate('Choose how many Muddy Puddles goods to buy'),
    ];
  }

  public function actBuyGoods($n)
  {
    $goods = $this->getRemainingGoods();
    $max = min(count($goods), $this->getPlayer()->countReserveResource(CLAY));

    if ($n < 1 || $n > $max) {
      throw new \BgaVisibleSystemException('Invalid Muddy Puddles quantity');
    }

    $childs = [$this->payNode([CLAY => $n])];
    foreach (array_slice($goods, 0, $n) as $good) {
      $childs[] = $this->receiveNode($good['id'], true);
    }

    Engine::insertAsChild([
      'type' => NODE_SEQ,
      'childs' => $childs,
    ]);
  }
}
