<?php

namespace ARK\Cards\Projects;

class P139_PrimateManagementPlan extends \ARK\Models\Projects\PlanProject
{
  public function __construct($row)
  {
    parent::__construct($row);
    $this->supported = [MW];
    $this->id = 'P139_PrimateManagementPlan';
    $this->number = 139;
    $this->name = clienttranslate('Primate Management Plan');
    $this->desc = clienttranslate('Requires 2 **primate** icons.');
    $this->icon = PRIMATE;
    $this->slots[2]['gain'][SEARCH_CARD] = $this->icon;
    $this->slots[0]['gain'][CLEVER] = 1;
    $this->playedBonus = [CLEVER => 1];
  }
}
