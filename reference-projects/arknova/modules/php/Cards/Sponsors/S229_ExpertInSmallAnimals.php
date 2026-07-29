<?php

namespace ARK\Cards\Sponsors;

class S229_ExpertInSmallAnimals extends \ARK\Models\Sponsor
{
  public function __construct($row)
  {
    parent::__construct($row);
    $this->id = 'S229_ExpertInSmallAnimals';
    $this->number = 229;
    $this->name = clienttranslate('Expert In Small Animals');
    $this->lvl = 5;
    $this->effects = [
      IMMEDIATE => [clienttranslate('Gain 1 appeal for each small animal in your zoo.')],
      PASSIVE => [
        clienttranslate('Every time you play a small animal, pay 3 money less for the animal than indicated on the Animal card.'),
        clienttranslate(
          'Small animals are animals that require a standard enclosure of 1 or 2 spaces, as well as Petting Zoo animals.'
        ),
      ],
    ];
    $this->person = true;
  }

  public function getImmediate()
  {
    $n = $this->getPlayer()
      ->getPlayedAnimal()
      ->filter(function ($animal) {
        return $animal->isSmall();
      })
      ->count();

    return $n == 0 ? [] : [[APPEAL => $n]];
  }
}
