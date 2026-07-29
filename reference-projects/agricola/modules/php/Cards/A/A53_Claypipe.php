<?php
namespace AGR\Cards\A;
use AGR\Core\Globals;

class A53_Claypipe extends \AGR\Models\MinorImprovement
{
  public function __construct($row)
  {
    parent::__construct($row);
    $this->id = 'A53_Claypipe';
    $this->name = clienttranslate('Claypipe');
    $this->deck = 'A';
    $this->number = 53;
    $this->category = FOOD_PROVIDER;
    $this->desc = [
      clienttranslate(
        'In the returning home phase of each round, if you gained at least 7 building resources in the preceding work phase, you get 2 <FOOD>.'
      ),
    ];
    $this->cost = [
      CLAY => 1,
    ];

    $this->rulings = [
      clienttranslate('Count the total of all gains during the work phase, even if building resources were lost in the process.'),
      clienttranslate('Building resources gained at the start of a round don\'t count.'),
    ];
  }

  public function isListeningTo($event)
  {
    return ($this->isActionEvent($event, 'Gain') ||
      $this->isActionEvent($event, 'Receive') ||
      $this->isActionEvent($event, 'Exchange') ||
      $this->isCollectEvent($event) ||
      ($this->isPlayerEvent($event) && $event['type'] == 'ReturnHome'));
  }

  public function onBuy($player)
  {
    $this->setExtraDatas('counter',0);
    $this->setInfobox('0 / 7');

    // also check resources obtained before playing (only if currently in work phase)
    if (Globals::isWorkPhase()) {
      $g = Globals::getObtainedResourcesDuringWork();
      $resources = $g[$this->pId] ?? [];
      $total = 0;
      foreach ([WOOD, STONE, CLAY, REED] as $res) {
        $total += $resources[$res] ?? 0;
      }

      if ($total > 0) {
        $this->setCounter($total);
        Globals::setObtainedResourcesDuringWork([]);
      }
    }
  }

  public function onPlayerReturnHome($player, $event)
  {
    // check legacy method for already active games
    // also check resources obtained before playing
    $g = Globals::getObtainedResourcesDuringWork();
    $resources = $g[$this->pId] ?? [];
    $total = 0;
    foreach ([WOOD, STONE, CLAY, REED] as $res) {
      $total += $resources[$res] ?? 0;
    }
    $total += ($this->getExtraDatas('counter') ?? 0);

    if (($this->getExtraDatas('counter') ?? 0) >= 7 || $total >= 7) {
      return [
        'type' => NODE_SEQ,
        'childs' => [
          $this->gainNode([FOOD => 2]),
          [
            'action' => SPECIAL_EFFECT,
            'args' => [
              'cardId' => $this->id,
              'method' => 'setCounter',
              'args' => [0],
            ],
          ]
        ]
      ];
    } else {
      return [
        'action' => SPECIAL_EFFECT,
        'args' => [
          'cardId' => $this->id,
          'method' => 'setCounter',
          'args' => [0],
        ],
      ];
    }
  }

  public function onPlayerAfterObtain($player, $event)
  {
    if (!Globals::isWorkPhase()) {
      return;
    }

    $meeples = $event['meeples'];
    $n = 0;
    foreach ($meeples as $meeple) {
      if (in_array($meeple['type'], [CLAY, WOOD, REED, STONE])) {
        $n++;
      }
    }
    if ($n > 0) {
      return [
        'action' => SPECIAL_EFFECT,
        'args' => [
          'cardId' => $this->id,
          'method' => 'increaseCounter',
          'args' => [$n],
        ],
      ];
    }
  }

  public function onPlayerAfterCollect($player, $event)
  {
    return $this->onPlayerAfterObtain($player, $event);
  }

  public function onPlayerAfterGain($player, $event)
  {
    return $this->onPlayerAfterObtain($player, $event);
  }

  public function onOpponentAfterGain($player, $event)
  {
    return $this->onPlayerAfterObtain($player, $event);
  }

  public function onPlayerAfterReceive($player, $event)
  {
    return $this->onPlayerAfterObtain($player, $event);
  }

  public function onOpponentAfterReceive($player, $event)
  {
    return $this->onPlayerAfterObtain($player, $event);
  }

  public function onPlayerAfterExchange($player, $event)
  {
    if (!Globals::isWorkPhase()) {
      return;
    }

    $trades = $event['trades'];
    $exchanges = $event['exchanges'];
    $n = 0;

    foreach ($trades as $tradeIndex) {
      if (is_array($tradeIndex) && $tradeIndex['source'] == 'discard') {
        continue;
      }
      $exchange = $exchanges[$tradeIndex] ?? null;

      if ($exchange) {
        foreach ($exchange['to'] as $resource => $amount) {
          if (in_array($resource, [CLAY, WOOD, REED, STONE])) {
            $n += $amount;
          }
        }
      }
    }

    if ($n >= 0) {
      return [
        'action' => SPECIAL_EFFECT,
        'args' => [
          'cardId' => $this->id,
          'method' => 'increaseCounter',
          'args' => [$n],
        ],
      ];
    }
  }

  public function isIndependentSetCounter()
  {
    return true;
  }

  public function setCounter($n)
  {
    $this->setExtraDatas('counter', $n);

    if (is_null($this->getInfobox())) {
      $this->setInfobox($n . ' / 7');
    } else {
      $this->updateInfobox($n . ' / 7');
    }
  }

  public function isIndependentIncreaseCounter()
  {
    return true;
  }

  public function increaseCounter($n)
  {
    $this->setExtraDatas('counter', ($this->getExtraDatas('counter') ?? 0) + $n);

    if (is_null($this->getInfobox())) {
      $this->setInfobox($n . ' / 7');
    } else {
    $this->updateInfobox($this->getExtraDatas('counter') . ' / 7');
    }
  }
}
