<?php

namespace ARK\Cards\Sponsors;

class S239_ExpertInPredators extends \ARK\Models\Sponsor
{
  public function __construct($row)
  {
    parent::__construct($row);
    $this->id = 'S239_ExpertInPredators';
    $this->number = 239;
    $this->name = clienttranslate('Expert In Predators');
    $this->lvl = 4;
    $this->categories = [PREDATOR];
    $this->effects = [
      PASSIVE => [clienttranslate('For each predator icon that is played in any zoo, gain 3 money (per icon).')],
    ];

    $this->listeningIcon = PREDATOR;
    $this->listeningMode = ALL_ZOO;
    $this->listeningBonuses = [[MONEY => 3]];
    $this->person = true;
  }
}
