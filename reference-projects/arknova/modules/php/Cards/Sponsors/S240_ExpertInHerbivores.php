<?php

namespace ARK\Cards\Sponsors;

class S240_ExpertInHerbivores extends \ARK\Models\Sponsor
{
  public function __construct($row)
  {
    parent::__construct($row);
    $this->id = 'S240_ExpertInHerbivores';
    $this->number = 240;
    $this->name = clienttranslate('Expert In Herbivores');
    $this->lvl = 4;
    $this->categories = [HERBIVORE];
    $this->effects = [
      PASSIVE => [clienttranslate('For each herbivore icon that is played in any zoo, gain 3 money (per icon).')],
    ];

    $this->listeningIcon = HERBIVORE;
    $this->listeningMode = ALL_ZOO;
    $this->listeningBonuses = [[MONEY => 3]];
    $this->person = true;
  }
}
