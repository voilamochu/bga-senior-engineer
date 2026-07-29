<?php

namespace ARK\Cards\Actions;

class ActionBuild1 extends ActionBuild
{
  public function __construct($row)
  {
    parent::__construct($row);
    $this->number = 1;
    $this->descI = [
      clienttranslate('Build **1 building** with a maximum size of <STRENGTH:X>.'),
      clienttranslate('Pay <MONEY:2> per space.'),
      clienttranslate('Available: **Kiosk, pavilion, aquariums, standard enclosures** and **petting zoo**.'),
      clienttranslate('You may pay <MONEY:3> to build 1 additional pavilion.')
    ];
    $this->descII = [
      clienttranslate('Build **1 or more different buildings** with a total maximum size of <STRENGTH:X>.'),
      clienttranslate('Pay <MONEY:2> per space.'),
      clienttranslate('Newly available: **Large Bird Aviary** and **Reptile House**.'),
      clienttranslate('You may pay <MONEY:2> to build 1 additional pavilion.')
    ];

    $this->tooltip = [
      clienttranslate("<SIDE_I> You may build 1 additional pavilion. You may do this even if you build a pavilion as part of your regular build action and even if you exceed the strength of your build action by 1 by building this pavilion. Pay a total cost of 3 money for the additional pavilion (the regular cost of 2 plus an extra cost of 1). You may build all buildings (including the additional pavilion) in an order of your choice."),
      clienttranslate("<SIDE_II> Same as Side I, but the cost for the additional pavilion is reduced to 2 money (the regular cost).")
    ];
  }
}
