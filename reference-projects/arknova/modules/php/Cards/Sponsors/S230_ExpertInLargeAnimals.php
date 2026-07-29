<?php

namespace ARK\Cards\Sponsors;

class S230_ExpertInLargeAnimals extends \ARK\Models\Sponsor
{
  public function __construct($row)
  {
    parent::__construct($row);
    $this->id = 'S230_ExpertInLargeAnimals';
    $this->number = 230;
    $this->name = clienttranslate('Expert In Large Animals');
    $this->lvl = 4;
    $this->effects = [
      IMMEDIATE => [clienttranslate('Gain 2 appeal for each large animal in your zoo.')],
      PASSIVE => [
        clienttranslate('Every time you play a large animal, pay 4 money less for the animal than indicated on the Animal card.'),
        clienttranslate('Large animals are animals that require a standard enclosure of 4 or 5 spaces.'),
      ],
    ];
    $this->person = true;
  }

  public function getImmediate()
  {
    $n = $this->getPlayer()
      ->getPlayedAnimal()
      ->filter(function ($animal) {
        return $animal->isLarge();
      })
      ->count();

    return $n == 0 ? [] : [[APPEAL => 2 * $n]];
  }
}
