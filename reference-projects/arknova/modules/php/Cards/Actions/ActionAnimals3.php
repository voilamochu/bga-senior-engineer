<?php

namespace ARK\Cards\Actions;

class ActionAnimals3 extends ActionAnimals
{
  public function __construct($row)
  {
    parent::__construct($row);
    $this->number = 3;
    $this->descI = [clienttranslate('Play animals cards.'), 'ANIMALS-I', clienttranslate('The **first animal** you play cost <MONEY:2> less.')];
    $this->descII = [
      clienttranslate('Play animals.'),
      'ANIMALS-II',
      clienttranslate('Additionally, for each animal you play, you may pay <MONEY:2> to gain <APPEAL:1>.')
    ];

    $this->tooltip[] = clienttranslate("<SIDE_I> Reduce the cost of the first animal you play with this action by 2 (to a minimum of 0).");
    $this->tooltip[] = clienttranslate("<SIDE_II> For each animal you play with this action and only once per animal, you may pay 2 additional money to gain 1 additional appeal. (Do not reduce the cost of the first animal like you did on Side I.)");
  }
}
