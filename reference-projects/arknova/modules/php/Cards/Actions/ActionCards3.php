<?php

namespace ARK\Cards\Actions;

class ActionCards3 extends ActionCards
{
  public function __construct($row)
  {
    parent::__construct($row);
    $this->number = 3;
    $this->descI = [
      clienttranslate('**BREAK** <BREAK:2>'),
      'CARDS-I3',
    ];
    $this->descII = [
      clienttranslate('**BREAK** <BREAK:2>'),
      'CARDS-II3',
    ];
    $this->tooltip[] = clienttranslate('You may snap instead of drawing cards with lower strength than usual (<SIDE_I> strength 3+; <SIDE_II> strength 2+).');
    $this->tooltip[] = clienttranslate("<SIDE_II> When the strength of your action is 5, you may snap 2 cards. Don't refill in between.");
  }

  public function getParameters()
  {
    return [
      1 => [[], [1, 1, 0], [1, 0, 0], [2, 1, 1], [2, 0, 1], [3, 1, 1]],
      2 => [[], [1, 0, 0], [2, 1, 1], [2, 0, 1], [3, 1, 1], [4, 1, 2]],
    ];
  }
}
