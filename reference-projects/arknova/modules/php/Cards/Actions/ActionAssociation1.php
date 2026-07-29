<?php

namespace ARK\Cards\Actions;

class ActionAssociation1 extends ActionAssociation
{
  public function __construct($row)
  {
    parent::__construct($row);
    $this->number = 1;
    $this->descI = [
      clienttranslate('Perform **1 association task** with a maximum value of <STRENGTH:X>.'),
      clienttranslate('Take **partner zoos** from the **supply** (instead of the Association board).'),
      clienttranslate('You may take partner zoos you **already have**.')
    ];
    $this->descII = [
      clienttranslate('Perform **1 or more different association tasks** with a total maximum value of <STRENGTH:X>.'),
      clienttranslate('In addition, you may make 1 **donation**.'),
      clienttranslate('Take **partner zoos** and **universities** from the supply.'),
      clienttranslate('You may take those you already have.')
    ];
    $this->tooltip[] = clienttranslate("<SIDE_I> If you use this action to take a partner zoo, you must take it from the supply, not from the Association board. You may take a partner zoo you already have (even if you already have more than 1 of it). If there are no more instances of a partner zoo in the supply, you can no longer take it, even if the last instance is still on the Association board. If you have multiple partner zoos of the same continent, each of them reduces the cost of each animal you play from that continent.");
    $this->tooltip[] = clienttranslate("<SIDE_II> Same as Side I, but it also applies to universities. You can also take any new university that is still available. Take it and leave the generic university where it is. Having multiples of the university that sets your hand limit to 5 does not increase your hand limit even further.");
  }
}
