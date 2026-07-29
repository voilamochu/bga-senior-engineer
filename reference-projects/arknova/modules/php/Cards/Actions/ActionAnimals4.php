<?php

namespace ARK\Cards\Actions;

class ActionAnimals4 extends ActionAnimals
{
  public function __construct($row)
  {
    parent::__construct($row);
    $this->number = 4;
    $this->descI = [clienttranslate('Play animals cards.'), 'ANIMALS-I', clienttranslate('<MARK> **(Mark)**')];
    $this->descII = [
      clienttranslate('Play animals cards.'),
      'ANIMALS-II',
      clienttranslate('Gain <REPUTATION:1> for any Animal card you play from the display that has **your marker on it**. <MARK> **(Mark)**.')
    ];

    $this->tooltip[] = clienttranslate("<SIDE_I> After finishing this action: Place 1 of your player tokens on an Animal card in the display that does not have a player token (from any player) on it yet. When a card with your player token on it is discarded from the display, the card goes to your hand instead. When a card with your player token on it leaves the display in any other way , gain 2 money. (Animal ability Mark)");
    $this->tooltip[] = clienttranslate("<SIDE_II> When playing an Animal card from the display that has one of your player counters on it, you gain 1 reputation (in addition to the 2 money from the Mark ability). Then, after finishing this action, Animal ability Mark.");
  }

  public function getAfterFinishingFlow($strength = null)
  {
    return [
      'action' => MARK,
      'args' => ['n' => 1]
    ];
  }
}
