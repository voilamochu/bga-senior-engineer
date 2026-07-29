<?php

namespace ARK\Cards\Sponsors;

class S278_AmazonHouse extends UniqueBuildingSponsor
{
  public function __construct($row)
  {
    parent::__construct($row);
    $this->supported = [MW];
    $this->id = 'S278_AmazonHouse';
    $this->number = 278;
    $this->name = clienttranslate('Amazon House');
    $this->lvl = 6;
    $this->effects = [
      IMMEDIATE => [clienttranslate('Place the Amazon house unique building on your zoo map (adjacent to at least 1 rock and 1 water space).')],
    ];
    $this->enclosureRequirements = [
      WATER => 1,
      ROCK => 1,
    ];
    $this->enclosure = "amazon";
    $this->prerequisites = [
      UPGRADED_SPONSORS_CARD => 1,
      AMERICAS => 1,
      REPUTATION => 6,
    ];
    $this->categories = [SEA_ANIMAL, HERBIVORE, PRIMATE];
    $this->reputation = 1;
    $this->conservation = 2;
    $this->appeal = 4;
  }
}
