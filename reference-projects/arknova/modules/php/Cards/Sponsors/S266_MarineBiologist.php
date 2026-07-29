<?php

namespace ARK\Cards\Sponsors;

class S266_MarineBiologist extends \ARK\Models\Sponsor
{
  public function __construct($row)
  {
    parent::__construct($row);
    $this->supported = [MW];
    $this->id = 'S266_MarineBiologist';
    $this->number = 266;
    $this->name = clienttranslate('Marine Biologist');
    $this->lvl = 4;
    $this->effects = [
      PASSIVE => [clienttranslate('For each sea animal icon that is played in any zoo, gain 3 money (per icon).')],
    ];
    $this->wave = true;

    $this->categories = [SEA_ANIMAL];
    $this->listeningIcon = SEA_ANIMAL;
    $this->listeningMode = ALL_ZOO;
    $this->listeningBonuses = [[MONEY => 3]];
    $this->person = true;
  }
}
