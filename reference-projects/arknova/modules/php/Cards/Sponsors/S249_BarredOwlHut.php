<?php
namespace ARK\Cards\Sponsors;

class S249_BarredOwlHut extends UniqueBuildingSponsor
{
  public function __construct($row)
  {
    parent::__construct($row);
    $this->id = 'S249_BarredOwlHut';
    $this->number = 249;
    $this->name = clienttranslate('Barred Owl Hut');
    $this->lvl = 6;
    $this->enclosure = 'owl';
    $this->categories = [BIRD];
    $this->effects = [
      IMMEDIATE => [clienttranslate('Place the Barred Owl Hut unique building on your zoo map.')],
      PASSIVE => [
        clienttranslate(
          'For each bird icon you play into your zoo, draw 2 cards from the deck. Keep 1 of them and discard the other (Perception 2).'
        ),
      ],
    ];
    $this->listeningIcon = \BIRD;
    $this->listeningBonuses = [[PERCEPTION => 2]];
  }
}
