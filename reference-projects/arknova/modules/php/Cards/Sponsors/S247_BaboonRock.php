<?php
namespace ARK\Cards\Sponsors;

class S247_BaboonRock extends UniqueBuildingSponsor
{
  public function __construct($row)
  {
    parent::__construct($row);
    $this->id = 'S247_BaboonRock';
    $this->number = 247;
    $this->name = clienttranslate('Baboon Rock');
    $this->lvl = 6;
    $this->enclosureRequirements = [
      ROCK => 1,
    ];
    $this->enclosure = 'baboon';
    $this->categories = [PRIMATE];
    $this->effects = [
      IMMEDIATE => [
        clienttranslate('Place the Baboon Rock unique building on your zoo map (adjacent to at least 1 rock space).'),
      ],
      PASSIVE => [clienttranslate('For each primate icon you play into your zoo, gain 2 appeal.')],
      ENDGAME => [clienttranslate('Gain 1 conservation point if you have 6 or more primate icons in your zoo.')],
    ];
    $this->listeningIcon = \PRIMATE;
    $this->listeningBonuses = [[APPEAL => 2]];
  }

  public function score()
  {
    $n = $this->countIcon(PRIMATE);
    $this->scoreConservation($n, [6 => 1]);
  }
}
