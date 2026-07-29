<?php

namespace ARK\Cards\Projects;

class P136_ReptileManagementPlan extends \ARK\Models\Projects\PlanProject
{
  public function __construct($row)
  {
    parent::__construct($row);
    $this->supported = [MW];
    $this->id = 'P136_ReptileManagementPlan';
    $this->number = 136;
    $this->name = clienttranslate('Reptile Management Plan');
    $this->desc = clienttranslate('Requires 2 **reptile** icons.');
    $this->icon = REPTILE;
    $this->slots[2]['gain'][SEARCH_CARD] = $this->icon;
    $this->slots[0]['gain'][SUNBATHING] = 2;
    $this->playedBonus = [SUNBATHING => 2];
  }
}
