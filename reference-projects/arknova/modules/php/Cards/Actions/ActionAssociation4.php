<?php

namespace ARK\Cards\Actions;

class ActionAssociation4 extends ActionAssociation
{
  public function __construct($row)
  {
    parent::__construct($row);
    $this->number = 4;
    $this->descI = [
      clienttranslate('Perform **1 association task** with a maximum value of <STRENGTH:X>.'),
      clienttranslate('At <STRENGTH:5> you may place this action card on <STRENGTH:1> and instead perform another action.'),
    ];
    $this->descII = [
      clienttranslate('Perform **1 or more different association tasks** with a total maximum value of <STRENGTH:X>.'),
      clienttranslate('In addition, you may make 1 **donation**.'),
      clienttranslate('Instead of making a donation, you may <TAKE-IN-RANGE-OR-DECK>.'),
    ];
    $this->tooltip[] = clienttranslate("<SIDE_I> When using this action card at a strength of 5, you may opt to do nothing but move the action card to slot 1 and then do another action after that (Animal ability Determination). You do not need to have an association worker in your supply to do that.");
    $this->tooltip[] = clienttranslate("<SIDE_II> Instead of making a donation, you may draw 1 card from the deck or from within reputation range. Do this at the end of the Association action and you must have performed at least 1 association task during this action to do this (just like you would need to make a donation).");
  }

  public function getFlow($strength = null)
  {
    $flow = parent::getFlow($strength);
    if ($strength < 5 || $this->getLevel() == 2) return $flow;

    return [
      'type' => NODE_XOR,
      'childs' => [
        $flow,
        [
          'action' => TAKE_BONUS,
          'source' => clienttranslate('Association4'),
          'args' => [
            'noIcon' => true,
            'type' => DETERMINATION,
            'n' => 1,
            'source' => clienttranslate('Association4')
          ]
        ]
      ]
    ];
  }

  public function canBePlayed($player, $strength = null)
  {
    $strength = $strength ?? $this->getStrength();
    if ($this->getLevel() == 1 && $strength >= 5) return true;
    return parent::canBePlayed($player, $strength);
  }
}
