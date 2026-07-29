<?php

namespace AGR\Cards\A;

use AGR\Models\MinorImprovement;
use function array_key_exists;
use AGR\Helpers\CardRulings;

class A17_ReclamationPlow extends MinorImprovement
{
  public function __construct($row)
  {
    parent::__construct($row);
    $this->id = 'A17_ReclamationPlow';
    $this->name = clienttranslate('Reclamation Plow');
    $this->deck = 'A';
    $this->number = 17;
    $this->category = FARM_PLANNER;
    $this->desc = [
      clienttranslate(
        'After the next time you take animals from an accumulation space and accommodate all of them on your farm, you can plow 1 field.'
      ),
    ];
    $this->cost = [
      WOOD => '1',
    ];
    $this->isArtifexOrBubulcus = true;

    $this->rulings = array_merge(
      CardRulings::fromKeys([
      'PARTIAL_ACCOMMODATION_NEUTRAL',
      ]),
      [
      clienttranslate('It is legal to avoid accommodating all animals even if you can, e.g. by cooking them with a Fireplace.'),
      ]
    );
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
        [
          'action' => PLOW,
          'optional' => !$presentOption,
          'args' => [
            'source' => $this->name,
          ],
        ],
        [
          'action' => SPECIAL_EFFECT,
          'args' => [
            'cardId' => $this->id,
            'method' => 'markCard',
          ]
        ],
      ]
    ];
  }

  public function markCard()
  {
    $this->setInfobox("✓");
  }
}
