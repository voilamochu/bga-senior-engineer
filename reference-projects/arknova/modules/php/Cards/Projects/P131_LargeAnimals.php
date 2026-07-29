<?php

namespace ARK\Cards\Projects;

class P131_LargeAnimals extends \ARK\Models\Projects\Project
{
  public function __construct($row)
  {
    parent::__construct($row);
    $this->supported = [BASE];
    $this->id = 'P131_LargeAnimals';
    $this->number = 131;
    $this->name = clienttranslate('Large Animals');
    $this->icon = 'ANIMAL-SIZE-4';
    $this->desc = clienttranslate('Requires **large animals** in your zoo.');
    $this->slots = [
      [
        'condition' => 4,
        'gain' => [CONSERVATION => 4],
      ],
      [
        'condition' => 3,
        'gain' => [CONSERVATION => 3],
      ],
      [
        'condition' => 2,
        'gain' => [CONSERVATION => 2],
      ],
    ];
    $this->details = ['type' => 'animals', 'category' => 'large'];
  }

  public function canPlaySlot($player, $slot, $bonusIcon = 0)
  {
    $cards = $player->getPlayedAnimal()->filter(function ($animal) {
      return $animal->isLarge();
    });

    return $cards->count() >= $slot['condition'];
  }
}
