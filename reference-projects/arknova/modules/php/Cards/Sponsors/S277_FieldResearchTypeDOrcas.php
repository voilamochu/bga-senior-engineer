<?php

namespace ARK\Cards\Sponsors;

class S277_FieldResearchTypeDOrcas extends \ARK\Models\Sponsor
{
  public function __construct($row)
  {
    parent::__construct($row);
    $this->supported = [MW];
    $this->id = 'S277_FieldResearchTypeDOrcas';
    $this->number = 277;
    $this->name = clienttranslate('Field Research Type D Orcas');
    $this->lvl = 3;
    $this->effects = [
      IMMEDIATE => [clienttranslate('Gain 3 reputation')],
    ];
    $this->wave = true;
    $this->prerequisites = [
      SCIENCE => 2,
      SEA_ANIMAL => 1
    ];
    $this->categories = [SCIENCE];
    $this->reputation = 3;
  }
}
