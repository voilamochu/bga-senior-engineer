<?php

namespace ARK\Cards\Actions;

class ActionCards2 extends ActionCards
{
  public function __construct($row)
  {
    parent::__construct($row);
    $this->number = 2;
    $this->descI = [
      clienttranslate('**First: <DIGGING:1> (Digging 1)**'),
      clienttranslate('**BREAK** <BREAK:2>'),
      'CARDS-I',
    ];
    $this->descII = [
      clienttranslate('First: <DIGGING:2> (Digging 2)'),
      clienttranslate('**BREAK** <BREAK:2>'),
      'CARDS-II',
    ];
    $this->tooltip[] = clienttranslate('Choose up to X times: Discard 1 card from the display and replenish OR discard 1 card from your hand to draw 1 from the deck (Animal ability __Digging X__). Do this before drawing new cards. (<SIDE_I> X=1; <SIDE_II> X=2)');
  }

  public function getFlow($strength = null)
  {
    $flow = parent::getFlow($strength);
    $flow['childs'][] = $flow['childs'][1];
    $flow['childs'][1] = [
      'action' => DIGGING,
      'args' => ['n' => $this->getLevel()]
    ];
    return $flow;
  }
}
