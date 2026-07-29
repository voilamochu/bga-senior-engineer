<?php

namespace ARK\Cards\Actions;

class ActionAssociation3 extends ActionAssociation
{
  public function __construct($row)
  {
    parent::__construct($row);
    $this->number = 3;
    $this->descI = [
      clienttranslate('Perform **1 association task** with a maximum value of <STRENGTH:X>.'),
      clienttranslate('Gain 1 <XTOKEN>-marker if <STRENGTH:X> if higher than the value of the task performed.'),
    ];
    $this->descII = [
      clienttranslate('Perform **1 or more different association tasks** with a total maximum value of <STRENGTH:X>.'),
      clienttranslate('In addition, you may make 1 **donation**, for which you pay <MONEY:1> less for each <XTOKEN>-marker you have.'),
    ];
    $this->tooltip[] = clienttranslate("<SIDE_I> If the strength of your action is higher than required for the task you carry out (not merely equal), gain 1 X-token.");
    $this->tooltip[] = clienttranslate("<SIDE_II> Every time you use this action to make a donation, pay 1 money less for each X-token on your notepad (to a minimum of 0 money). You don't have to discard the X-tokens.");
  }
}
