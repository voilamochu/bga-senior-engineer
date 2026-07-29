<?php

namespace ARK\Cards\Sponsors;

class S238_Ornithologist extends \ARK\Models\Sponsor
{
  public function __construct($row)
  {
    parent::__construct($row);
    $this->id = 'S238_Ornithologist';
    $this->number = 238;
    $this->name = clienttranslate('Ornithologist');
    $this->lvl = 4;
    $this->categories = [BIRD];
    $this->effects = [
      PASSIVE => [clienttranslate('For each bird icon that is played in any zoo, gain 3 money (per icon).')],
    ];

    $this->listeningIcon = BIRD;
    $this->listeningMode = ALL_ZOO;
    $this->listeningBonuses = [[MONEY => 3]];
    $this->person = true;
  }
}
