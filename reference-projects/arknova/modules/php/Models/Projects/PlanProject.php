<?php

namespace ARK\Models\Projects;

class PlanProject extends Project
{
  public function __construct($row)
  {
    parent::__construct($row);
    $this->category = PROJECT_PLAN;
    $this->slots = [
      ['gain' => [CONSERVATION => 2]],
      ['gain' => [CONSERVATION => 2, REPUTATION => "SCIENCE/2"]],
      ['gain' => [CONSERVATION => 2]],
    ];
  }

  public function canPlaySlot($player, $slot, $bonusIcon = 0)
  {
    // No bonus icon since they are not basic plan
    $icons = $player->countCardIcons();
    return $icons[$this->icon] >= 2;
  }
}
