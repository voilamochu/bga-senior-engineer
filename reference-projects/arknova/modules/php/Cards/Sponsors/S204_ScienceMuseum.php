<?php

namespace ARK\Cards\Sponsors;

class S204_ScienceMuseum extends \ARK\Models\Sponsor
{
  public function __construct($row)
  {
    parent::__construct($row);
    $this->id = 'S204_ScienceMuseum';
    $this->number = 204;
    $this->name = clienttranslate('Science Museum');
    $this->lvl = 4;
    $this->categories = [SCIENCE];
    $this->prerequisites = [
      SCIENCE => 4,
    ];
    $this->effects = [
      IMMEDIATE => [clienttranslate('Gain 2 money for each research icon in your zoo.')],
      PASSIVE => [clienttranslate('For each research icon you play into your zoo, gain 1 conservation point.')],
    ];

    $this->listeningIcon = SCIENCE;
    $this->listeningBonuses = [[CONSERVATION => 1]];
  }

  public function getImmediate()
  {
    return [[MONEY => SCIENCE_SCIENCE]];
  }
}
