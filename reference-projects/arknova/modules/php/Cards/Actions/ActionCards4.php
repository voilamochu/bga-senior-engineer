<?php

namespace ARK\Cards\Actions;

class ActionCards4 extends ActionCards
{
  public function __construct($row)
  {
    parent::__construct($row);
    $this->number = 4;
    $this->descI = [
      clienttranslate('**BREAK** <BREAK:2>'),
      'CARDS-I',
      '<MONEY:2> : <CLEVER:1>'
    ];
    $this->descII = [
      clienttranslate('**BREAK** <BREAK:2>'),
      'CARDS-II',
      '<CLEVER:1>',
    ];
    $this->tooltip[] = clienttranslate('<SIDE_I> After finishing this action, you may pay 2 money to place any Action card on card slot 1 (Animal ability Clever).');
    $this->tooltip[] = clienttranslate('<SIDE_II> Same as Side I, but you do not have to pay money to place an Action card on card slot 1.');
  }

  public function getAfterFinishingFlow($strength = null)
  {
    return $this->getLevel() == 2 ? [
      'action' => CLEVER,
      'optional' => true,
    ] : [
      'type' => NODE_SEQ,
      'optional' => true,
      'childs' => [
        [
          'action' => PAY,
          'args' => ['n' => 2],
          'source' => clienttranslate('Cards4 effect')
        ],
        [
          'action' => CLEVER
        ]
      ]
    ];
  }
}
