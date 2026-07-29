<?php
namespace AGR\Cards\C;

use AGR\Managers\Meeples;
use AGR\Core\Notifications;
use AGR\Core\Engine;

class C120_AgriculturalLabourer extends \AGR\Models\Occupation
{
  public function __construct($row)
  {
    parent::__construct($row);
    $this->id = 'C120_AgriculturalLabourer';
    $this->name = clienttranslate('Agricultural Labourer');
    $this->deck = 'C';
    $this->number = 120;
    $this->category = BUILDING_RESOURCE_PROVIDER;
    $this->desc = [
      clienttranslate('Place 8 <CLAY> on this card. For each <GRAIN> you obtain, you also get 1 <CLAY> from this card.'),
    ];
    $this->players = '1+';
    $this->holder = true;
    $this->isCorbariusOrDulcinaria = true;
  }

  public function onBuy($player)
  {
    $created = Meeples::createResourceInLocation(CLAY, $this->id, $player->getId(), null, null, 8);
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
    $n = $this->getExtraDatas('C120Converted') ?? 0;
    return $this->isAnytime($event) && $n > 0;
  }

  public function onPlayerAtAnytime($player, $event)
  {
    $n = $this->getExtraDatas('C120Converted') ?? 0;
    if ($n > 0) {
      return [
        'type' => NODE_SEQ,
        'childs' => [
          $this->unflagCardNode('C120Converted'),
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
    return clienttranslate('Gain 1 <CLAY>');
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
      $this->setExtraDatas('C120Converted', $count);
    }
  }

  public function gainNextResources($n)
  {
    $player = $this->getPlayer();
    $meeples = Meeples::getResourcesOnCard($this->id, null, null)->toArray();
    $n = min($n, count($meeples));
    $meepleIds = [];
    for ($i = 0; $i < $n; $i++) {
      $meepleIds[] = $meeples[count($meeples) - 1 - $i]['id'];
    }
    if (!empty($meepleIds)) {
      Engine::insertAsChild([
        'action' => RECEIVE,
        'args' => [
          'meeples' => $meepleIds,
          'player' => $player,
        ],
      ]);
    }
  }

  public function getGainNextResourcesDescription($n)
  {
    return [
      'log' => clienttranslate('Receive ${resources_desc} from ${card}'),
      'args' => [
        'resources_desc' => strval($n) . ' <CLAY>',
        'card' => $this->name,
      ],
    ];
  }
}