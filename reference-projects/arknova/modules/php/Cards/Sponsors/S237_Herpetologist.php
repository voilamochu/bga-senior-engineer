<?php

namespace ARK\Cards\Sponsors;

class S237_Herpetologist extends \ARK\Models\Sponsor
{
  public function __construct($row)
  {
    parent::__construct($row);
    $this->id = 'S237_Herpetologist';
    $this->number = 237;
    $this->name = clienttranslate('Herpetologist');
    $this->lvl = 4;
    $this->categories = [REPTILE];
    $this->effects = [
      PASSIVE => [clienttranslate('For each reptile icon that is played in any zoo, gain 3 money (per icon).')],
    ];

    $this->listeningIcon = REPTILE;
    $this->listeningMode = ALL_ZOO;
    $this->listeningBonuses = [[MONEY => 3]];
    $this->person = true;
  }
}
