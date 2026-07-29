<?php
namespace ARK\Cards\Sponsors;

class S251_PolarBearExhibit extends UniqueBuildingSponsor
{
  public function __construct($row)
  {
    parent::__construct($row);
    $this->id = 'S251_PolarBearExhibit';
    $this->number = 251;
    $this->name = clienttranslate('Polar Bear Exhibit');
    $this->lvl = 5;
    $this->enclosureRequirements = [
      WATER => 1,
    ];
    $this->enclosure = 'polar-bear';
    $this->categories = [PREDATOR, BEAR];
    $this->effects = [
      IMMEDIATE => [
        clienttranslate('Place the Polar Bear Exhibit unique building on your zoo map (adjacent to at least 1 water space).'),
      ],
      PASSIVE => [clienttranslate('For each bear icon that is played into any zoo, gain 2 appeal.')],
      ENDGAME => [clienttranslate('Gain 1 conservation point for 3–5 bear icons in your zoo; gain 2 for 6 or more.')],
    ];
    $this->listeningIcon = \BEAR;
    $this->listeningMode = ALL_ZOO;
    $this->listeningBonuses = [[APPEAL => 2]];
  }

  public function score()
  {
    $n = $this->countIcon(BEAR);
    $this->scoreConservation($n, [3 => 1, 6 => 2]);
  }
}
