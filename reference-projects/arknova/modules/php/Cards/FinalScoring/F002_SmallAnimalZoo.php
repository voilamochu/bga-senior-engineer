<?php
namespace ARK\Cards\FinalScoring;

class F002_SmallAnimalZoo extends \ARK\Models\FinalScoring
{
  public function __construct($row)
  {
    parent::__construct($row);
    $this->id = 'F002_SmallAnimalZoo';
    $this->number = 2;
    $this->name = clienttranslate('Small Animal Zoo');
    $this->icon = 'ANIMAL-SIZE-2';
    $this->desc = clienttranslate('Gain <CONSERVATION> for **small animals** in your zoo');
    $this->scoreMap = [3 => 1, 6 => 2, 8 => 3, 10 => 4];
  }

  public function getQuantity()
  {
    return $this->getPlayer()
      ->getPlayedAnimal()
      ->filter(function ($animal) {
        return $animal->getEnclosureSize() <= 2;
      })
      ->count();
  }
}
