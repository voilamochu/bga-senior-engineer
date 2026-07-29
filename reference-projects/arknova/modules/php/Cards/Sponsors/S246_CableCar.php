<?php
namespace ARK\Cards\Sponsors;

class S246_CableCar extends UniqueBuildingSponsor
{
  public function __construct($row)
  {
    parent::__construct($row);
    $this->id = 'S246_CableCar';
    $this->number = 246;
    $this->name = clienttranslate('Cable Car');
    $this->lvl = 6;
    $this->enclosureRequirements = [
      ROCK => 2,
    ];
    $this->enclosure = 'cable';
    $this->effects = [
      IMMEDIATE => [
        clienttranslate('Place the Aerial Cableway unique building on your zoo map (adjacent to at least 2 rock spaces).'),
      ],
      PASSIVE => [
        clienttranslate(
          'For each rock icon you play into your zoo, gain 2 appeal. You can find these on card 242 and otherwise as a requirement on the top left of cards.'
        ),
      ],
      ENDGAME => [clienttranslate('Gain 1 conservation point if you have 6 or more rock icons in your zoo.')],
    ];
    $this->listeningIcon = \ROCK;
    $this->listeningBonuses = [[APPEAL => 2]];
  }

  public function score()
  {
    $n = $this->countIcon(ROCK);
    $this->scoreConservation($n, [6 => 1]);
  }
}
