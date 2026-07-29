<?php
namespace AGR\Cards\E;

use AGR\Core\Engine;
use AGR\Core\Notifications;
use AGR\Managers\Meeples;

class E103_Wolf extends \AGR\Models\Occupation {
  public function __construct($row) {
    parent::__construct($row);
    $this->id = 'E103_Wolf';
    $this->name = clienttranslate('Wolf');
    $this->deck = 'E';
    $this->author = 'oxmond';
    $this->number = 103;
    $this->category = 'GOODS_-_GET';
    $this->desc = [
      clienttranslate(
        'Pile (from bottom to top) 1 <CLAY>, 1 <WOOD>, and 1 <GRAIN> on this card. Each time you get a good matching the top item, you can move that item to your supply and get 1 <PIG>.'
      ),
    ];
    $this->players = '1+';
    $this->holder = true;
  }

  public function onBuy($player) {
    $types = [CLAY, WOOD, GRAIN];

    $created = [];
    foreach($types as $i => $type) {
      $created = array_merge(
        $created,
        Meeples::createResourceInLocation($type, $this->id, $player->getId(), null, null, 1, $i)
      );
    }

    Notifications::accumulate(Meeples::getMany($created), true);
  }

  public function isListeningTo($event) {
    if($this->getNextResource() == null) {
      return false;
    }
    if ($this->isPlayerEvent($event) && $event['type'] == 'StartOfTurn') {
      return true;
    }
    if(
      $this->isActionEvent($event, 'Gain') ||
      $this->isActionEvent($event, 'Receive') ||
      $this->isActionEvent($event, 'Collect') ||
      $this->isActionEvent($event, 'Reap')
    ) {
      return true;
    }
    $player = $this->getPlayer();
    if(!$player->hasPlayedCard($this->id)) {
      return false;
    }
    if($this->isActionEvent($event, 'Exchange')) {
      $this->checkConverted($player, $event);
      return false;
    }
    $n = $this->getExtraDatas('E103Obtained') ?? 0;
    return $this->isAnytime($event) && $n > 0;
  }

  public function checkMaxMatch($player, $types) {
    $goods = Meeples::getResourcesOnCard($this->id);
    $left = [];
    $n = 0;
    foreach($goods as $good) {
      $left[] = $good['type'];
    }
    $left = array_reverse($left);
    foreach($left as $good) {
      $found = in_array($good, $types);
      if($found) {
        $n++;
      } else {
        break;
      }
    }
    return $n;
  }

  public function onPlayerStartOfTurn($player, $event)
  {
    return $this->unflagCardNode('usable');
  }

  public function onPlayerAtAnytime($player, $event) {
    $n = $this->getExtraDatas('E103Obtained') ?? 0;
    if ($n == 0 || !$this->isFlagged('usable')) {
      return;
    }

    $childs = [];
    for ($i = 1; $i <= $n; $i++) {
      $childs[] = [
        'type' => NODE_SEQ,
        'childs' => [
          [
            'action' => SPECIAL_EFFECT,
            'args' => [
              'cardId' => $this->id,
              'method' => 'gainNextResource',
              'args' => [$i]
            ]
          ],
          $this->gainNode([PIG => $i]),
        ]
      ];
    }

    return [
      'type' => NODE_SEQ,
      'childs' => [
        $this->unflagCardNode('usable'),
        $this->unflagCardNode('E103Obtained'),
        [
          'type' => NODE_XOR,
          'optional' => true,
          'childs' => $childs
        ],
      ]
    ];
  }

  public function onPlayerAfterObtain($player, $event) {
    $meeples = $event['meeples'];
    $types = [];

    foreach($meeples as $meeple) {
      $types[] = $meeple['type'];
    }

    $n = $this->checkMaxMatch($player, $types);
    if ($n == 0) {
      return;
    }

    $childs = [];
    for ($i = 1; $i <= $n; $i++) {
      $childs[] = [
        'type' => NODE_SEQ,
        'childs' => [
          [
            'action' => SPECIAL_EFFECT,
            'args' => [
              'cardId' => $this->id,
              'method' => 'gainNextResource',
              'args' => [$i]
            ]
          ],
          $this->gainNode([PIG => $i]),
        ]
      ];
    }

    return [
      'type' => NODE_SEQ,
      'optional' => true,
      'childs' => [
        $this->unflagCardNode('E103Obtained'),
        [
          'type' => NODE_XOR,
          'childs' => $childs
        ],
      ]
    ];
  }

  public function onPlayerAfterCollect($player, $event) {
    return $this->onPlayerAfterObtain($player, $event);
  }

  public function onPlayerAfterGain($player, $event) {
    return $this->onPlayerAfterObtain($player, $event);
  }

  public function onOpponentAfterGain($player, $event) {
    return $this->onPlayerAfterObtain($player, $event);
  }

  public function onPlayerAfterReceive($player, $event) {
    return $this->onPlayerAfterObtain($player, $event);
  }

  public function onOpponentAfterReceive($player, $event) {
    return $this->onPlayerAfterObtain($player, $event);
  }

  public function onPlayerAfterReap($player, $event) {
    $event['meeples'] = $event['crops'];
    return $this->onPlayerAfterObtain($player, $event);
  }

  public function onOpponentAfterReap($player, $event) {
    $event['meeples'] = $event['crops'];
    return $this->onPlayerAfterObtain($player, $event);
  }

  public function checkConverted($player, $event) {
    $trades = $event['trades'];
    $exchanges = $event['exchanges'];

    $types = [];
    foreach ($trades as $tradeIndex) {
      if (is_array($tradeIndex) && $tradeIndex['source'] == 'discard') {
        continue;
      }
      $exchange = $exchanges[$tradeIndex] ?? null;
      if ($exchange) {
        foreach($exchange['to'] as $resource => $amount) {
          $types[] = $resource;
        }
      }
    }

    $count = $this->checkMaxMatch($player, $types);
    if ($count >= 0) {
      $this->setExtraDatas('usable', true);
      $this->setExtraDatas('E103Obtained', $count);
    }
  }

  public function gainNextResource($n) {
    $player = $this->getPlayer();
    $goods = Meeples::getResourcesOnCard($this->id);
    $meeples = [];

    foreach ($goods as $meeple) {
      $meeples[] = $meeple;
    }
    $meeples = array_reverse($meeples);
    
    $children = [];
    for ($i = 0; $i < $n; $i++) {
      $meeple = $meeples[$i];
      $children[] = $this->receiveNode($meeple['id'], true, $player);
    }

    $flow = [
      'type' => NODE_SEQ,
      'childs' => $children,
    ];
    Engine::insertAsChild($flow);
  }

  public function getGainNextResourceDescription($n) {
    return [
      'log' => clienttranslate('Receive top ${resources_desc} goods from ${card}'),
      'args' => [
        'resources_desc' => strval($n),
        'card' => $this->name,
      ],
    ];
  }
}