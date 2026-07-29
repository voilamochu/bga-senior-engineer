<?php
namespace ARK\Cards\Projects;

class P130_SmallAnimals extends \ARK\Models\Projects\Project
{
  public function __construct($row)
  {
    parent::__construct($row);
    $this->id = 'P130_SmallAnimals';
    $this->number = 130;
    $this->name = clienttranslate('Small Animals');
    $this->desc = clienttranslate('Requires **small animals** in your zoo.');
    $this->icon = 'ANIMAL-SIZE-2';
    $this->slots = [
      [
        'condition' => 8,
        'gain' => [CONSERVATION => 4],
      ],
      [
        'condition' => 5,
        'gain' => [CONSERVATION => 3],
      ],
      [
        'condition' => 2,
        'gain' => [CONSERVATION => 2],
      ],
    ];
    $this->details = ['type' => 'animals', 'category' => 'small'];
  }

  public function canPlaySlot($player, $slot, $bonusIcon = 0)
  {
    $cards = $player->getPlayedAnimal()->filter(function ($animal) {
      return $animal->isSmall();
    });

    return $cards->count() >= $slot['condition'];
  }
}
