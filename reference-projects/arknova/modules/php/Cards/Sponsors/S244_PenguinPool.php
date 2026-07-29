<?php
namespace ARK\Cards\Sponsors;

class S244_PenguinPool extends UniqueBuildingSponsor
{
  public function __construct($row)
  {
    parent::__construct($row);
    $this->id = 'S244_PenguinPool';
    $this->number = 244;
    $this->name = clienttranslate('Penguin Pool');
    $this->lvl = 5;
    $this->enclosureRequirements = [
      WATER => 1,
    ];
    $this->enclosure = 'penguin';
    $this->categories = [BIRD];
    $this->prerequisites = [REPUTATION => 3];
    $this->effects = [
      IMMEDIATE => [
        clienttranslate('Place the Penguin Pool unique building on your zoo map (adjacent to at least 1 water space).'),
      ],
      PASSIVE => [clienttranslate('For each bird icon you play into your zoo, gain 2 appeal.')],
      ENDGAME => [clienttranslate('Gain 1 conservation point if you have 6 or more bird icons in your zoo.')],
    ];

    $this->listeningIcon = \BIRD;
    $this->listeningBonuses = [[APPEAL => 2]];
  }

  public function score()
  {
    $n = $this->countIcon(BIRD);
    $this->scoreConservation($n, [6 => 1]);
  }
}
