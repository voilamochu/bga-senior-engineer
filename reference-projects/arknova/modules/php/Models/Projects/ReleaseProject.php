<?php

namespace ARK\Models\Projects;

class ReleaseProject extends Project
{
  public function __construct($row)
  {
    parent::__construct($row);
    $this->category = PROJECT_RELEASE;
    $this->slots = [
      [
        'condition' => ['size' => 4],
        'gain' => [CONSERVATION => 5],
      ],
      [
        'condition' => ['size' => 3],
        'gain' => [CONSERVATION => 4],
      ],
      [
        'condition' => ['size' => 2],
        'gain' => [CONSERVATION => 3],
      ],
    ];
    $this->playedBonus = [REPUTATION => 1];
  }

  public function canPlaySlot($player, $slot, $bonusIcon = 0)
  {
    $cards = $player->getPlayedAnimal($this->icon)->filter(function ($card) {
      return $card->getLocation() != 'rescueStation'; // MAP10
    });
    $size = $slot['condition']['size'];
    $animalIds = [];

    foreach ($cards as $cId => $card) {
      $d = $card->getEnclosureSize() - $size;
      if (($size == 4 && $d >= 0) || ($size == 3 && $d == 0) || ($size == 2 && $d <= 0)) {
        $animalIds[] = $cId;
      }
    }

    return empty($animalIds) ? false : $animalIds;
  }
}
