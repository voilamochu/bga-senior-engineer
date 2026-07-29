<?php

namespace ARK\Cards\Projects;

class P137_HerbivoreManagementPlan extends \ARK\Models\Projects\PlanProject
{
  public function __construct($row)
  {
    parent::__construct($row);
    $this->supported = [MW];
    $this->id = 'P137_HerbivoreManagementPlan';
    $this->number = 137;
    $this->name = clienttranslate('Herbivore Management Plan');
    $this->desc = clienttranslate('Requires 2 **herbivore** icons.');
    $this->icon = HERBIVORE;
    $this->slots[2]['gain'][SEARCH_CARD] = $this->icon;
    $this->slots[0]['gain'][DIGGING] = 2;
    $this->playedBonus = [DIGGING => 2];
  }
}
