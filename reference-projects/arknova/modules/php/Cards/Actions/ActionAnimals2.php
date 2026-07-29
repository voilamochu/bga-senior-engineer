<?php

namespace ARK\Cards\Actions;

class ActionAnimals2 extends ActionAnimals
{
  public function __construct($row)
  {
    parent::__construct($row);
    $this->number = 2;
    $this->descI = [clienttranslate('Play animals cards.'), 'ANIMALS-I', clienttranslate('If you have no Animal card in hand after finishing this action: **Hunter 4**.')];
    $this->descII = [
      clienttranslate('Play animals cards.'),
      'ANIMALS-II',
      clienttranslate('If you have no Animal card in hand after finishing this action: **Hunter 6**.')
    ];
    $this->tooltip[] = clienttranslate("After finishing this action, if you have no Animal cards in your hand, you may reveal the X topmost cards of the deck. Choose 1 Animal card from these and add it to your hand. Discard the other cards. (Animal ability Hunter X) (<SIDE_I> X=4 cards; <SIDE_II> X=6 cards)");
  }

  public function getAfterFinishingFlow($strength = null)
  {
    return [
      'action' => ANIMALS2_HUNTER,
      'args' => ['upgraded' => $this->getLevel() == 2]
    ];
  }
}
