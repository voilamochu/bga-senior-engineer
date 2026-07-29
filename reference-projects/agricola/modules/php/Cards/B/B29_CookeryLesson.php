<?php

namespace AGR\Cards\B;

use AGR\Models\MinorImprovement;

class B29_CookeryLesson extends MinorImprovement
{
  public function __construct($row)
  {
    parent::__construct($row);
    $this->id = 'B29_CookeryLesson';
    $this->name = clienttranslate('Cookery Lesson');
    $this->deck = 'B';
    $this->number = 29;
    $this->category = POINTS_PROVIDER;
    $this->desc = [
      clienttranslate(
        'Each time you use a __Lessons__ action space and a cooking improvement on the same turn, you get 1 bonus <SCORE>.'
      ),
    ];
    $this->cost = [
      FOOD => '2',
    ];
    $this->extraVp = true;
    $this->isArtifexOrBubulcus = true;
    $this->implemented = true;

    $this->rulings = [
      clienttranslate('Cooking improvements have the bowl icon.'),
    ];
  }

  public function onBuy($player): ?array
  {
    if ($this->hasCookedThisTurn($player) && $this->hasUsedLessonsThisTurn($player)) {
      return [
        'type' => NODE_SEQ,
        'childs' => [
          $this->setUsedOnTurnIdNode(),
          $this->gainNode([SCORE => 1])
        ]
      ];
    } else {
      $this->setUsedOnTurnId('');
      return null;
    }
  }

  public function isListeningTo($event): bool
  {
    return (($this->isActionEvent($event, 'PlaceFarmer') && $event['actionCardType'] == 'Lessons') ||
        $this->isActionEvent($event, 'Exchange')) &&
      $this->usableThisTurn();
  }

  public function onPlayerAfterExchange($player, $event): ?array
  {
    if ($this->usableThisTurn() && $this->hasUsedLessonsThisTurn($player)) {
      return [
        'type' => NODE_SEQ,
        'childs' => [
          $this->setUsedOnTurnIdNode(),
          $this->gainNode([SCORE => 1])
        ]
      ];
    }
    return null;
  }

  public function onPlayerAfterPlaceFarmer($player, $event): ?array
  {
    if ($this->usableThisTurn() && $this->hasCookedThisTurn($player)) {
      return [
        'type' => NODE_SEQ,
        'childs' => [
          $this->setUsedOnTurnIdNode(),
          $this->gainNode([SCORE => 1])
        ]
      ];
    }
    return null;
  }
}
