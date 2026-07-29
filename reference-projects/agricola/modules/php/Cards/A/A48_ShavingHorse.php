<?php
namespace AGR\Cards\A;

use AGR\Managers\Meeples;
use AGR\Managers\PlayerCards;
use AGR\Core\Notifications;
use AGR\Core\Stats;
use AGR\Core\Engine;

class A48_ShavingHorse extends \AGR\Models\MinorImprovement
{
  public function __construct($row)
  {
    parent::__construct($row);
    $this->id = 'A48_ShavingHorse';
    $this->name = clienttranslate('Shaving Horse');
    $this->deck = 'A';
    $this->number = 48;
    $this->category = FOOD_PROVIDER;
    $this->desc = [
      clienttranslate(
        'Each time after you obtain at least 1 <WOOD>, if you then have 5 or more <WOOD> in your supply, you can exchange 1 <WOOD> for 3 <FOOD>. With 7 or more <WOOD>, you must do so.'
      ),
    ];
    $this->cost = [
      WOOD => '1',
    ];
    $this->isArtifexOrBubulcus = true;
    $this->bannedStrong1or2p = true;
    $this->bannedStrong3or4p = true;
  }

  public function isListeningTo($event)
  {
    if (
      $this->isActionEvent($event, 'Gain') ||
      $this->isActionEvent($event, 'Receive') ||
      $this->isActionEvent($event, 'Reap') ||
      $this->isActionEvent($event,'Collect')
    ) {
      return true;
    }
    $player = $this->getPlayer();
    if (!$player->hasPlayedCard($this->id)) {
      return false;
    }
    if ($this->isActionEvent($event, 'Exchange')) {
      $this->checkWoodIsConverted($event);
      return false;
    }
    $n = $this->getExtraDatas('A48Converted') ?? 0;
    return $this->isAnytime($event) && $n > 0;
  }

  public function onPlayerAtAnytime($player, $event)
  {
    $n = $this->getExtraDatas('A48Converted') ?? 0;
    if ($n > 0 && $player->countReserveResource(WOOD)>=7) {
      return [
        'type' => NODE_SEQ,
        'childs' => [
          $this->unflagCardNode('A48Converted'),
          $this->payGainNode([WOOD=>1],[FOOD=>3],null,false),
        ]
      ];
    }
    if ($n > 0 && $player->countReserveResource(WOOD)>=5) {
      return [
        'type' => NODE_SEQ,
        'childs' => [
          $this->unflagCardNode('A48Converted'),
          $this->payGainNode([WOOD=>1],[FOOD=>3],null,true),
        ]
      ];
    }
  }

  public function onPlayerAfterObtain($player, $event)
  {
    $meeples = $event['meeples'];
    $n = 0;
    foreach ($meeples as $meeple) {
      if ($meeple['type'] == WOOD) {
        $n++;
        break;
      }
    }
    if ($n > 0 && $player->countReserveResource(WOOD)>=7) {
      return $this->payGainNode([WOOD=>1],[FOOD=>3],null,false);
    }
    if ($n > 0 && $player->countReserveResource(WOOD)>=5) {
      return $this->payGainNode([WOOD=>1],[FOOD=>3],null,true);
    }
  }

  public function onPlayerAfterGain($player, $event)
  {
    return $this->onPlayerAfterObtain($player, $event);
  }


  public function onPlayerAfterReceive($player, $event)
  {
    return $this->onPlayerAfterObtain($player, $event);
  }


  public function onPlayerAfterReap($player, $event)
  {
    $event['meeples'] = $event['crops'];
    return $this->onPlayerAfterObtain($player, $event);
  }

  public function onPlayerAfterCollect($player, $event)
  {
    return $this->onPlayerAfterObtain($player, $event);
  }

  public function checkWoodIsConverted($event)
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
        $isWoodExchange = false;
        foreach ($exchange['to'] as $resource => $amount) {
          if ($resource == WOOD) {
            $isWoodExchange = true;
            break;
          }
        }
        if ($isWoodExchange) {
          $count++;
        }
      }
    }

    if ($count >= 0) {
      $this->setExtraDatas('A48Converted', $count);
    }
  }
}