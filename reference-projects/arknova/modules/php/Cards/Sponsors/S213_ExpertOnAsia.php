<?php

namespace ARK\Cards\Sponsors;

class S213_ExpertOnAsia extends \ARK\Models\Sponsor
{
  public function __construct($row)
  {
    parent::__construct($row);
    $this->id = 'S213_ExpertOnAsia';
    $this->number = 213;
    $this->name = clienttranslate('Expert On Asia');
    $this->lvl = 5;
    $this->continents = [ASIA];
    $this->effects = [
      IMMEDIATE => [clienttranslate('Gain 1 appeal for each Asia icon in your zoo.')],
      PASSIVE => [
        clienttranslate(
          'For each Asia icon you play into your zoo, you may place 1 pavilion on your zoo map for free. The usual building rules apply.'
        ),
      ],
    ];

    $this->listeningIcon = ASIA;
    $this->listeningBonuses = [[PAVILION => 1]];
    $this->person = true;
  }

  public function getImmediate()
  {
    return [[APPEAL => ASIA]];
  }
}
