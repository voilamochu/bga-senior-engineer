<?php

namespace AGR\Cards\C;

use AGR\Actions\Exchange;
use AGR\Models\MinorImprovement;

class C53_GypsysCrock extends MinorImprovement
{
  public function __construct($row)
  {
    parent::__construct($row);
    $this->id = 'C53_GypsysCrock';
    $this->name = clienttranslate("Gypsy's Crock");
    $this->deck = 'C';
    $this->number = 53;
    $this->category = FOOD_PROVIDER;
    $this->desc = [
      clienttranslate(
        'Each time you use a cooking improvement to turn 2 goods into <FOOD> at the same time, you get 1 additional <FOOD>.'
      ),
    ];
    $this->vp = 1;
    $this->cost = [
      CLAY => '2',
    ];
    $this->isCorbariusOrDulcinaria = true;
  }

  public function isListeningTo($event): bool
  {
    $player = $this->getPlayer();
    if (!$player->hasPlayedCard($this->id)) {
      return false;
    }
    if ($this->isActionEvent($event, 'Exchange')) {
      $this->checkPairIsCooked($event);
      return false;
    }
    $foodGain = floor($this->getExtraDatas('C53Cooked') / 2) ?? 0;
    return $this->isAnytime($event) && $foodGain > 0;
  }

  public function onPlayerAtAnytime($player, $event)
  {
    $foodGain = floor($this->getExtraDatas('C53Cooked') / 2) ?? 0;
    if ($foodGain > 0) {
      return [
        'type' => NODE_SEQ,
        'childs' => [
          $this->unflagCardNode('C53Cooked'),
          $this->gainNode([FOOD => $foodGain]),
        ]
      ];
    }
  }

  public function checkPairIsCooked($event)
  {
    $trades = $event['trades'];
    $exchanges = $event['exchanges'];
    $foodExchangeCount = 0;

    foreach ($trades as $tradeIndex) {
      if (is_array($tradeIndex) && $tradeIndex['source'] == 'discard') {
        continue;
      }
      $exchange = $exchanges[$tradeIndex] ?? null;

      if ($exchange) {
        $source = $exchange['source'] ?? null;

        if ($source && Exchange::isCookingSource($source)) {
          $isFoodExchange = false;

          foreach ($exchange['to'] as $resource => $amount) {
            if ($resource == FOOD) {
              $isFoodExchange = true;
              break;
            }
          }

          if ($isFoodExchange) {
            $foodExchangeCount++;
          }
        }
      }
    }

    if ($foodExchangeCount >= 2) {
      $this->setExtraDatas('C53Cooked', $foodExchangeCount);
    }
  }
}