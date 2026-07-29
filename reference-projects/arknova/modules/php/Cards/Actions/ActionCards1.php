<?php

namespace ARK\Cards\Actions;

class ActionCards1 extends ActionCards
{
  public function __construct($row)
  {
    parent::__construct($row);
    $this->number = 1;
    $this->descI = [
      clienttranslate('**BREAK** <BREAK:2>'),
      'CARDS-I1',
    ];
    $this->descII = [
      clienttranslate('**BREAK** <BREAK:2>'),
      'CARDS-II1',
    ];
    $this->tooltip[] = clienttranslate('No matter the strength of your action, keep all cards drawn. You do not need to discard 1 card.');
  }

  public function getParameters()
  {
    return [
      1 => [[], [1, 0, 0], [1, 0, 0], [2, 0, 0], [2, 0, 0], [3, 0, 1]],
      2 => [[], [1, 0, 0], [2, 0, 0], [2, 0, 1], [3, 0, 1], [4, 0, 1]],
    ];
  }
}
