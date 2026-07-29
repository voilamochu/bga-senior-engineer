<?php

namespace AGR\Cards\D;

use AGR\Actions\Exchange;
use AGR\Models\MinorImprovement;

class D56_FatstockStretcher extends MinorImprovement
{
  public function __construct($row)
  {
    parent::__construct($row);
    $this->id = 'D56_FatstockStretcher';
    $this->name = clienttranslate('Fatstock Stretcher');
    $this->deck = 'D';
    $this->number = 56;
    $this->category = FOOD_PROVIDER;
    $this->desc = [
      clienttranslate(
        'Each time you turn a <SHEEP> or <PIG> into <FOOD> using a cooking improvement, you get 1 additional <FOOD>.'
      ),
    ];
    $this->cost = [
      WOOD => '1',
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
      $this->checkSheepOrPigCooked($event);
      return false;
    }
    $foodGain = $this->getExtraDatas('D56Cooked') ?? 0;
    return $this->isAnytime($event) && $foodGain > 0;
  }

  public function onPlayerAtAnytime($player, $event)
  {
    $foodGain = $this->getExtraDatas('D56Cooked') ?? 0;
    if ($foodGain > 0) {
      return [
        'type' => NODE_SEQ,
        'childs' => [
          $this->unflagCardNode('D56Cooked'),
          $this->gainNode([FOOD => $foodGain]),
        ]
      ];
    }
  }

  public function checkSheepOrPigCooked($event)
  {
    $trades = $event['trades'];
    $exchanges = $event['exchanges'];
    $foodToGain = 0;
    foreach ($trades as $tradeIndex) {
      if (is_array($tradeIndex) && $tradeIndex['source'] == 'discard') {
        continue;
      }
      $exchange = $exchanges[$tradeIndex] ?? null;

      if ($exchange) {
        $source = $exchange['source'] ?? null;

        if ($source && Exchange::isCookingSource($source)) {
          $fromSheepOrPig = false;
          $toFood = false;
          foreach ($exchange['from'] as $resource => $amount) {
            if ($resource == SHEEP || $resource == PIG) {
              $fromSheepOrPig = true;
              break;
            }
          }
          foreach ($exchange['to'] as $resource => $amount) {
            if ($resource == FOOD) {
              $toFood = true;
              break;
            }
          }

          if ($fromSheepOrPig && $toFood) {
            $foodToGain++;
          }
        }
      }
    }
    if ($foodToGain > 0) {
      $this->setExtraDatas('D56Cooked', $foodToGain);
    }
  }
}