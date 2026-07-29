<?php

namespace ARK\Cards\Sponsors;

class S212_ExpertOnAustralia extends \ARK\Models\Sponsor
{
  public function __construct($row)
  {
    parent::__construct($row);
    $this->id = 'S212_ExpertOnAustralia';
    $this->number = 212;
    $this->name = clienttranslate('Expert On Australia');
    $this->lvl = 5;
    $this->continents = [AUSTRALIA];
    $this->effects = [
      IMMEDIATE => [clienttranslate('Gain 1 appeal for each Australia icon in your zoo.')],
      PASSIVE => [
        clienttranslate(
          'For each Australia icon you play into your zoo, you may place 1 card from your hand under this card to gain 2 appeal (Pouch 1). Cards under this card no longer have a function.'
        ),
      ],
    ];

    $this->listeningIcon = AUSTRALIA;
    $this->listeningBonuses = [[POUCH => 1]];
    $this->person = true;
  }

  public function getImmediate()
  {
    return [[APPEAL => AUSTRALIA]];
  }
}
