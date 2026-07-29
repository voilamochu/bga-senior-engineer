<?php

namespace AGR\Cards\B;

use AGR\Models\MinorImprovement;
use AGR\Helpers\CardRulings;

class B34_SpecialFood extends MinorImprovement
{
  public function __construct($row)
  {
    parent::__construct($row);
    $this->id = 'B34_SpecialFood';
    $this->name = clienttranslate('Special Food');
    $this->deck = 'B';
    $this->number = 34;
    $this->category = POINTS_PROVIDER;
    $this->desc = [
      clienttranslate(
        'The next time you take animals from an accumulation space and accommodate all of them on your farm, you get 1 bonus <SCORE> for each of these animals.'
      ),
    ];
    $this->prerequisite = clienttranslate('No Animal');
    $this->extraVp = true;
    $this->isArtifexOrBubulcus = true;

    $this->rulings = CardRulings::fromKeys([
      'PARTIAL_ACCOMMODATION_NEUTRAL',
    ]);
  }

  public function isBuyable($player, $ignoreResources = false, $args = [])
  {
    if (array_sum($player->countAnimalsOnBoard()) > 0) {
      return false;
    }
    return parent::isBuyable($player, $ignoreResources, $args);
  }

  public function isListeningTo($event)
  {
    // Has the effect been used already?
    if ($this->isFlagged()) return;

    $player = $this->getPlayer();

    // Is it an animal collection space?
    if ($this->isCollectEvent($event, SHEEP) ||
      $this->isCollectEvent($event, PIG) ||
      $this->isCollectEvent($event, CATTLE)) {
      $this->setExtraDatas('animalsBeforeCollecting', $player->countAnimalsOnBoard());
      return true;
    }

    if ($player->hasPlayedCard('A137_RiverineShepherd')) {
      // Check if action is taken (but not replacement checks)
      if (isset($event['action']) && $event['action'] == 'SpecialEffect' &&
        isset($event['args']['cardId']) && $event['args']['cardId'] == 'A137_RiverineShepherd' &&
        ($event['method'] ?? '') != 'computeReplaceSpecialEffect') {
        return true;
      }

      // If action is taken, reset usedRiverineShepherd and check for collect event
      if ($this->isCollectEvent($event, REED) && $this->getExtraDatas('usedRiverineShepherd')) {
        $this->setExtraDatas('usedRiverineShepherd', false);
        $this->setExtraDatas('animalsBeforeCollecting', $player->countAnimalsOnBoard());
        return true;
      }
    }
  }

  public function onPlayerBeforeSpecialEffect($player, $event)
  {
    $this->setExtraDatas('usedRiverineShepherd', true);
  }

  public function onPlayerAfterCollect($player, $event)
  {
    if ($event['actionCardId'] == 'ActionReedBank') {
      $collectedAnimals = [SHEEP => 1, PIG => 0, CATTLE => 0];
    } else {
      $collectedAnimals = [SHEEP => 0, PIG => 0, CATTLE => 0];
      foreach ($event['meeples'] as $meeple) {
        $type = $meeple['type'];
        if (array_key_exists($type, $collectedAnimals)) {
          $collectedAnimals[$type]++;
        }
      }
    }

    $presentOption = false;
    $animalsBeforeCollecting = $this->getExtraDatas('animalsBeforeCollecting');
    $animalsOnBoard = $player->countAnimalsOnBoard();
    foreach ($collectedAnimals as $type => $amt) {
      if ($animalsOnBoard[$type] < $amt) {
        return;
      }

      // If animals are cooked / discarded but player already had more animals then taken - give option to select if
      // animals were cooked / discarded from existing or taken ones
      if ($animalsOnBoard[$type] < $amt + $animalsBeforeCollecting[$type]) {
        $presentOption = true;
      }
    }

    return [
      'type' => NODE_SEQ,
      'optional' => $presentOption,
      'childs' => [
        $this->flagCardNode(),
        $this->gainNode([SCORE => array_sum($collectedAnimals)]),
      ],
    ];
  }
}