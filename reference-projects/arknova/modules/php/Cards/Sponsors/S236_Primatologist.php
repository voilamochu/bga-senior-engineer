<?php

namespace ARK\Cards\Sponsors;

class S236_Primatologist extends \ARK\Models\Sponsor
{
  public function __construct($row)
  {
    parent::__construct($row);
    $this->id = 'S236_Primatologist';
    $this->number = 236;
    $this->name = clienttranslate('Primatologist');
    $this->lvl = 4;
    $this->categories = [PRIMATE];
    $this->effects = [
      PASSIVE => [clienttranslate('For each primate icon that is played in any zoo, gain 3 money (per icon).')],
    ];

    $this->listeningIcon = PRIMATE;
    $this->listeningMode = ALL_ZOO;
    $this->listeningBonuses = [[MONEY => 3]];
    $this->person = true;
  }
}
