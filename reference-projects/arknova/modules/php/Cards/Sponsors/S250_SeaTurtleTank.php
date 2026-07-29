<?php

namespace ARK\Cards\Sponsors;

class S250_SeaTurtleTank extends UniqueBuildingSponsor
{
  public function __construct($row)
  {
    parent::__construct($row);
    $this->supported = [BASE];
    $this->id = 'S250_SeaTurtleTank';
    $this->number = 250;
    $this->name = clienttranslate('Sea Turtle Tank');
    $this->lvl = 5;
    $this->enclosureRequirements = [
      WATER => 1,
    ];
    $this->enclosure = 'sea-turtle';
    $this->categories = [REPTILE];
    $this->effects = [
      IMMEDIATE => [
        clienttranslate('Place the Sea Turtle Tank unique building on your zoo map (adjacent to at least 1 water space).'),
      ],
      PASSIVE => [
        clienttranslate(
          'For each reptile icon you play into your zoo, you may sell up to 2 cards from your hand for 4 money each (Sunbathing 2). Place the sold cards on the discard pile.'
        ),
      ],
    ];
    $this->listeningIcon = REPTILE;
    $this->listeningBonuses = [[SUNBATHING => 2]];
  }
}
