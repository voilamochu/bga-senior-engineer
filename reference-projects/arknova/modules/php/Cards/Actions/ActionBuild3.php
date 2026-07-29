<?php

namespace ARK\Cards\Actions;

class ActionBuild3 extends ActionBuild
{
  public function __construct($row)
  {
    parent::__construct($row);
    $this->number = 3;
    $this->descI = [
      clienttranslate('Build **1 building** with a maximum size of <STRENGTH:X>**+1**.'),
      clienttranslate('Pay <MONEY:2> per space.'),
      clienttranslate('Available: **Kiosk, pavilion, aquariums, standard enclosures** and **petting zoo**.'),
    ];
    $this->descII = [
      clienttranslate('Build **1 or more different buildings** with a total maximum size of <STRENGTH:X>**+1**.'),
      clienttranslate('Pay <MONEY:2> per space.'),
      clienttranslate('Newly available: **Large Bird Aviary** and **Reptile House**.'),
      clienttranslate('You may build more than 1 of the **same standard enclosure**.')
    ];
    $this->tooltip = [
      clienttranslate("<SIDE_I> You may build with a strength of X+1."),
      clienttranslate("<SIDE_II> Same as Side I, but you may build as many of each standard enclosure as you want (the limit of X+1 strength does still apply). This does not allow you to build more than 1 kiosk and 1 pavilion.")
    ];
  }

  public function canBePlayed($player, $strength = null)
  {
    $strength = $strength ?? $this->getStrength();
    return $this->getAction(['strength' => $strength + 1, 'lvl' => $this->getLevel()])->isDoable($player);
  }

  public function getFlow($strength = null)
  {
    $strength = $strength ?? $this->getStrength();
    $lvl = $this->getLevel();
    return [
      'action' => BUILD,
      'args' => [
        'strength' => $strength + 1,
        'lvl' => $lvl,
        'number' => $this->number,
      ],
    ];
  }
}
