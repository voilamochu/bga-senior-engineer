<?php
namespace ARK\Cards\Sponsors;

class S245_Aquarium extends UniqueBuildingSponsor
{
  public function __construct($row)
  {
    parent::__construct($row);
    $this->id = 'S245_Aquarium';
    $this->number = 245;
    $this->name = clienttranslate('Aquarium');
    $this->lvl = 5;
    $this->enclosureRequirements = [
      WATER => 2,
    ];
    $this->enclosure = 'aquarium';
    $this->prerequisites = [REPUTATION => 3];
    $this->effects = [
      IMMEDIATE => [clienttranslate('Place the Aquarium unique building on your zoo map (adjacent to at least 2 water spaces).')],
      PASSIVE => [
        clienttranslate(
          'For each water icon you play into your zoo, gain 2 appeal. You can find these on card 241 and otherwise as a requirement on the upper-left corners of cards.'
        ),
      ],
      ENDGAME => [clienttranslate('Gain 1 conservation point if you have 6 or more water icons in your zoo.')],
    ];
    $this->listeningIcon = \WATER;
    $this->listeningBonuses = [[APPEAL => 2]];
  }

  public function score()
  {
    $n = $this->countIcon(WATER);
    $this->scoreConservation($n, [6 => 1]);
  }
}
