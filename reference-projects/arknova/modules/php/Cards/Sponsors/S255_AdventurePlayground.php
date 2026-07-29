<?php
namespace ARK\Cards\Sponsors;

class S255_AdventurePlayground extends UniqueBuildingSponsor
{
  public function __construct($row)
  {
    parent::__construct($row);
    $this->id = 'S255_AdventurePlayground';
    $this->number = 255;
    $this->name = clienttranslate('Adventure Playground');
    $this->lvl = 3;
    $this->appeal = 4;
    $this->enclosureRequirements = [
      ROCK => 1,
    ];
    $this->enclosure = 'adventure';
    $this->effects = [
      IMMEDIATE => [
        clienttranslate('Gain 4 appeal when you play this card.'),
        clienttranslate('Place the Adventure Playground unique building on your zoo map (adjacent to at least 1 rock space).'),
      ],
    ];
  }
}
