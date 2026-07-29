<?php

namespace ARK\Cards\Projects;

class P138_SeaAnimalManagementPlan extends \ARK\Models\Projects\PlanProject
{
  public function __construct($row)
  {
    parent::__construct($row);
    $this->supported = [MW];
    $this->id = 'P138_SeaAnimalManagementPlan';
    $this->number = 138;
    $this->name = clienttranslate('Sea Animal Management Plan');
    $this->desc = clienttranslate('Requires 2 **sea animal** icons.');
    $this->icon = SEA_ANIMAL;
    $this->slots[2]['gain'][SEARCH_CARD] = $this->icon;
    $this->slots[0]['gain'][REEF] = 1;
    $this->playedBonus = [REPUTATION => 1];
  }
}
