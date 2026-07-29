<?php

namespace ARK\Cards\Projects;

class P135_BirdManagementPlan extends \ARK\Models\Projects\PlanProject
{
  public function __construct($row)
  {
    parent::__construct($row);
    $this->supported = [MW];
    $this->id = 'P135_BirdManagementPlan';
    $this->number = 135;
    $this->name = clienttranslate('Bird Management Plan');
    $this->desc = clienttranslate('Requires 2 **bird** icons.');
    $this->icon = BIRD;
    $this->slots[2]['gain'][SEARCH_CARD] = $this->icon;
    $this->slots[0]['gain'][KIOSK_OR_PAVILION] = 1;
    $this->playedBonus = [KIOSK_OR_PAVILION => 1];
  }
}
