<?php

namespace ARK\Models\Projects;

class ProjectIcon extends Project
{
  public function __construct($row)
  {
    parent::__construct($row);
    $this->category = PROJECT_ICONS;
    $this->slots = [
      [
        'condition' => 5,
        'gain' => [CONSERVATION => 5],
      ],
      [
        'condition' => 4,
        'gain' => [CONSERVATION => 4],
      ],
      [
        'condition' => 2,
        'gain' => [CONSERVATION => 2],
      ],
    ];
  }

  public function canPlaySlot($player, $slot, $bonusIcon = 0)
  {
    $t = explode('_', $this->location);
    if ($this->type != CARD_BASE_PROJECT || $t[0] != 'base') {
      $bonusIcon = 0;
    }
    $icons = $player->countCardIcons();
    return $icons[$this->icon] + $bonusIcon >= $slot['condition'];
  }
}
