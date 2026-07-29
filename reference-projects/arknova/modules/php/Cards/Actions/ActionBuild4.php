<?php

namespace ARK\Cards\Actions;

class ActionBuild4 extends ActionBuild
{
  public function __construct($row)
  {
    parent::__construct($row);
    $this->number = 4;
    $this->descI = [
      clienttranslate('Build **1 building** with a maximum size of <STRENGTH:X>.'),
      clienttranslate('Pay <MONEY:2> per space.'),
      clienttranslate('Available: **Kiosk, pavilion, aquariums, standard enclosures** and **petting zoo**.'),
      clienttranslate('You may pay <MONEY:2> to **cover 1 rock** or **water** space.')
    ];
    $this->descII = [
      clienttranslate('Build **1 or more different buildings** with a total maximum size of <STRENGTH:X>.'),
      clienttranslate('Pay <MONEY:2> per space.'),
      clienttranslate('Newly available: **Large Bird Aviary** and **Reptile House**.'),
      clienttranslate('You may **cover 1 rock** or **water** space. Gain <MONEY:2> if you do.')
    ];

    $this->tooltip = [
      clienttranslate("<SIDE_I> You may pay 2 money to cover 1 water or rock space as if it were a building space. This only applies when using this action, not when building in any other way or when placing a unique building."),
      clienttranslate("<SIDE_II> Same as Side I, but instead of having to pay 2 money, you gain 2 money when you cover a water or rock space. You may still only build on 1 water or rock space per action.")
    ];
  }
}
