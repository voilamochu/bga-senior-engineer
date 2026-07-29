<?php

namespace ARK\Cards\Projects;

class P134_PredatorManagementPlan extends \ARK\Models\Projects\PlanProject
{
  public function __construct($row)
  {
    parent::__construct($row);
    $this->supported = [MW];
    $this->id = 'P134_PredatorManagementPlan';
    $this->number = 134;
    $this->name = clienttranslate('Predator Management Plan');
    $this->desc = clienttranslate('Requires 2 **predator** icons.');
    $this->icon = PREDATOR;
    $this->slots[2]['gain'][SEARCH_CARD] = $this->icon;
    $this->slots[0]['gain'][HUNTER] = PREDATOR;
    $this->playedBonus = [HUNTER => PREDATOR];
  }
}
