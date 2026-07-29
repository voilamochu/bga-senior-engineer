<?php

namespace AGR\Cards\B;

use AGR\Models\MinorImprovement;

class B75_WoodWorkshop extends MinorImprovement
{
  public function __construct($row)
  {
    parent::__construct($row);
    $this->id = 'B75_WoodWorkshop';
    $this->name = clienttranslate('Wood Workshop');
    $this->deck = 'B';
    $this->number = 75;
    $this->category = BUILDING_RESOURCE_PROVIDER;
    $this->desc = [clienttranslate('Each time before you play or build an improvement, you get 1 <WOOD>.')];
    $this->cost = [
      CLAY => '1',
    ];
    $this->prerequisite = clienttranslate('1 Occupation');
    $this->occupationPrerequisites = ['min' => 1];
    $this->isArtifexOrBubulcus = true;
    $this->implemented = true;

    $this->rulings = [
      clienttranslate('You can pay for an improvement with the <WOOD> given by this card.'),
    ];
  }

  public function isListeningTo($event)
  {
    return $this->isBeforeEvent($event, 'Improvement');
  }

  public function onPlayerBeforeImprovement($player, $event)
  {
    return $this->gainNode([WOOD => 1]);
  }

  // player may can build an improvement after get the wood from this card.
  // it is wrong to add some check in PlayerCards, or to reduce wood cost in isBuyable.
  // for example, player may convert the gained wood to food by using A48_ShavingHorse, then play a card that requires food.
  public function onPlayerIsDoable($player, &$args)
  {
    if ($args['isDoable']) {
      return;
    }

    if ($args['action'] == IMPROVEMENT) {
      $args['isDoable'] = true;
    }
  }
}
