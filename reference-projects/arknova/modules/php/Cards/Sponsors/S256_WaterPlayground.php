<?php
namespace ARK\Cards\Sponsors;

class S256_WaterPlayground extends UniqueBuildingSponsor
{
  public function __construct($row)
  {
    parent::__construct($row);
    $this->id = 'S256_WaterPlayground';
    $this->number = 256;
    $this->name = clienttranslate('Water Playground');
    $this->lvl = 3;
    $this->appeal = 4;
    $this->enclosureRequirements = [
      WATER => 1,
    ];
    $this->enclosure = 'water-playground';
    $this->effects = [
      IMMEDIATE => [
        clienttranslate('Gain 4 appeal when you play this card.'),
        \clienttranslate('Place the Water Playground unique building on your zoo map (adjacent to at least 1 water space).'),
      ],
    ];
  }
}
