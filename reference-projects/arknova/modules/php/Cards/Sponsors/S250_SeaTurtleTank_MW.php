<?php

namespace ARK\Cards\Sponsors;

class S250_SeaTurtleTank_MW extends S250_SeaTurtleTank
{
  public function __construct($row)
  {
    parent::__construct($row);
    $this->supported = [MW];
    $this->id = 'S250_SeaTurtleTank_MW';
    $this->enclosureRequirements = [
      WATER => 2,
    ];
    $this->categories = [REPTILE, SEA_ANIMAL];
    $this->effects = [
      IMMEDIATE => [
        clienttranslate('Place the Sea Turtle Tank unique building on your zoo map (adjacent to at least 2 water space).'),
      ],
      PASSIVE => [
        clienttranslate(
          'For each reptile icon you play into your zoo, you may sell up to 2 cards from your hand for 4 money each (Sunbathing 2). Place the sold cards on the discard pile.'
        ),
      ],
    ];
  }
}
