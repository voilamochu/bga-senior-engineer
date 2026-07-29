<?php

namespace ARK\Cards\FinalScoring;

class F001_LargeAnimalZoo extends \ARK\Models\FinalScoring
{
  public function __construct($row)
  {
    parent::__construct($row);
    $this->supported = [BASE];
    $this->id = 'F001_LargeAnimalZoo';
    $this->number = 1;
    $this->name = clienttranslate('Large Animal Zoo');
    $this->icon = 'ANIMAL-SIZE-4';
    $this->desc = clienttranslate('Gain <CONSERVATION> for **large animals** in your zoo');
    $this->scoreMap = [1 => 1, 2 => 2, 4 => 3, 5 => 4];
  }

  public function getQuantity()
  {
    return $this->getPlayer()
      ->getPlayedAnimal()
      ->filter(function ($animal) {
        return $animal->getEnclosureSize() >= 4;
      })
      ->count();
  }
}
