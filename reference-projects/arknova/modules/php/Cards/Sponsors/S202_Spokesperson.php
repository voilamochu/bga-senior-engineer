<?php

namespace ARK\Cards\Sponsors;

class S202_Spokesperson extends \ARK\Models\Sponsor
{
  public function __construct($row)
  {
    parent::__construct($row);
    $this->id = 'S202_Spokesperson';
    $this->number = 202;
    $this->name = clienttranslate('Spokesperson');
    $this->lvl = 5;
    $this->categories = [SCIENCE];
    $this->prerequisites = [
      SCIENCE => 1,
    ];
    $this->effects = [
      PASSIVE => [clienttranslate('Each time you play a research icon into your zoo, gain <REPUTATION:1>')],
    ];

    $this->listeningIcon = SCIENCE;
    $this->listeningBonuses = [[REPUTATION => 1]];
    $this->person = true;
  }
}
