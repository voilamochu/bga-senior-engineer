<?php
namespace AGR\Cards\B;

use AGR\Managers\Meeples;
use AGR\Managers\PlayerCards;
use AGR\Core\Notifications;
use AGR\Core\Stats;
use AGR\Core\Engine;

class B21_HayloftBarn extends \AGR\Models\MinorImprovement
{
  public function __construct($row)
  {
    parent::__construct($row);
    $this->id = 'B21_HayloftBarn';
    $this->name = clienttranslate('Hayloft Barn');
    $this->deck = 'B';
    $this->number = 21;
    $this->category = ACTIONS_BOOSTER;
    $this->desc = [
      clienttranslate(
        'Place 4 <FOOD> on this card. Each time you obtain at least 1 <GRAIN>, you also get 1 <FOOD> from this card. Once it is empty, you get a __Family Growth Even without Room__ action.'
      ),
    ];
    $this->cost = [
      WOOD => '3',
    ];
    $this->prerequisite = clienttranslate('1 Occupation');
    $this->occupationPrerequisites = ['min' => 1];
    $this->holder = true;
    $this->isArtifexOrBubulcus = true;
    $this->bannedStrong1or2p = true;
    $this->bannedStrong3or4p = true;
  }

  public function onBuy($player)
  {
    $created = Meeples::createResourceInLocation(FOOD, $this->id, $player->getId(), null, null, 4);
    Notifications::accumulate(Meeples::getMany($created), true);
  }

  public function isListeningTo($event)
  {
    if ($this->getNextResource() == null) {
      return false;
    }
    if (
      $this->isActionEvent($event, 'Gain') ||
      $this->isActionEvent($event, 'Receive') ||
      $this->isActionEvent($event, 'Reap')
    ) {
      return true;
    }
    $player = $this->getPlayer();
    if (!$player->hasPlayedCard($this->id)) {
      return false;
    }
    if ($this->isActionEvent($event, 'Exchange')) {
      $this->checkGrainIsConverted($event);
      return false;
    }
    $n = $this->getExtraDatas('B21Converted') ?? 0;
    return $this->isAnytime($event) && $n > 0;
  }

  public function onPlayerAtAnytime($player, $event)
  {
    $n = $this->getExtraDatas('B21Converted') ?? 0;
    if ($n > 0) {
      return [
        'type' => NODE_SEQ,
        'childs' => [
          $this->unflagCardNode('B21Converted'),
          [
            'action' => SPECIAL_EFFECT,
            'args' => [
              'cardId' => $this->id,
              'method' => 'gainNextResources',
              'args' => [$n],
            ],
          ],
        ]
      ];
    }
  }

  public function onPlayerAfterObtain($player, $event)
  {
    $meeples = $event['meeples'];
    $n = 0;
    foreach ($meeples as $meeple) {
      if ($meeple['type'] == GRAIN) {
        $n++;
        break;
      }
    }
    if ($n > 0) {
      return [
        'action' => SPECIAL_EFFECT,
        'args' => [
          'cardId' => $this->id,
          'method' => 'gainNextResources',
          'args' => [$n],
        ],
      ];
    }
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

  public function onPlayerAfterReap($player, $event)
  {
    $event['meeples'] = $event['crops'];
    return $this->onPlayerAfterObtain($player, $event);
  }

  public function onOpponentAfterReap($player, $event)
  {
    $event['meeples'] = $event['crops'];
    return $this->onPlayerAfterObtain($player, $event);
  }

  public function getGainNextResourceDescription()
  {
    return clienttranslate('Gain 1 <FOOD>');
  }

  public function checkGrainIsConverted($event)
  {
    $trades = $event['trades'];
    $exchanges = $event['exchanges'];
    $count = 0;

    foreach ($trades as $tradeIndex) {
      if (is_array($tradeIndex) && $tradeIndex['source'] == 'discard') {
        continue;
      }
      $exchange = $exchanges[$tradeIndex] ?? null;

      if ($exchange) {
        $isGrainExchange = false;
        foreach ($exchange['to'] as $resource => $amount) {
          if ($resource == GRAIN) {
            $isGrainExchange = true;
            break;
          }
        }
        if ($isGrainExchange) {
          $count++;
        }
      }
    }

    if ($count >= 0) {
      $this->setExtraDatas('B21Converted', $count);
    }
  }

  public function gainNextResources($n)
  {
    $player = $this->getPlayer();
    for ($i = 0; $i < $n; $i++) {
      $meeple = $this->getNextResource();
      if (!is_null($meeple)) {
        Meeples::receiveResource($player, $meeple);
        Notifications::receiveResource($player, $meeple);
        Stats::incCardsFood($player, 1);
        $meeple = $this->getNextResource();
        if (is_null($meeple) && $player->countFarmers() <= 4 && $player->hasFarmerInReserve()) {
          $flow = [
          'action' => WISHCHILDREN,
          'args' => ['cardLocation' => $this->id],
          'source' => $this->name
          ];
          Engine::insertAsChild($flow);
        }
      }
    }
  }

  public function getGainNextResourcesDescription($n)
  {
    return [
      'log' => clienttranslate('Receive ${resources_desc} from ${card}'),
      'args' => [
        'resources_desc' => strval($n) . ' <FOOD>',
        'card' => $this->name,
      ],
    ];
  }
}