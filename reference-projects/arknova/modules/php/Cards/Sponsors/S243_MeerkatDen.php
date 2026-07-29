<?php
namespace ARK\Cards\Sponsors;

class S243_MeerkatDen extends UniqueBuildingSponsor
{
  public function __construct($row)
  {
    parent::__construct($row);
    $this->id = 'S243_MeerkatDen';
    $this->number = 243;
    $this->name = clienttranslate('Meerkat Den');
    $this->lvl = 5;
    $this->enclosureRequirements = [
      ROCK => 1,
    ];
    $this->enclosure = 'meerkat';
    $this->categories = [HERBIVORE];
    $this->prerequisites = [REPUTATION => 3];
    $this->effects = [
      IMMEDIATE => [
        clienttranslate('Place the Meerkat Den unique building on your zoo map (adjacent to at least 1 rock space).'),
      ],
      PASSIVE => [clienttranslate('For each herbivore icon you play into your zoo, gain 2 appeal.')],
      ENDGAME => [clienttranslate('Gain 1 conservation point if you have 6 or more herbivore icons in your zoo.')],
    ];

    $this->listeningIcon = \HERBIVORE;
    $this->listeningBonuses = [[APPEAL => 2]];
  }

  public function score()
  {
    $n = $this->countIcon(\HERBIVORE);
    $this->scoreConservation($n, [6 => 1]);
  }
}
