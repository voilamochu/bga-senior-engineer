<?php

namespace ARK\Cards\Sponsors;

class S271_ExcavationSite extends UniqueBuildingSponsor
{
  public function __construct($row)
  {
    parent::__construct($row);
    $this->supported = [MW];
    $this->id = 'S271_ExcavationSite';
    $this->number = 271;
    $this->name = clienttranslate('Excavation Site');
    $this->lvl = 5;
    $this->effects = [
      IMMEDIATE => [
        clienttranslate('Place the excavation site unique building on your zoo map.'),
        clienttranslate('Gain any placement bonuses you get for placing this unique building twice in a row.')
      ],
      ENDGAME => [clienttranslate('Gain 1 conservation point if all placement bonuses on your zoo map are covered.')],
    ];
    $this->categories = [SCIENCE];
    $this->enclosure = 'excavation';
  }

  public function score()
  {
    $map = $this->getPlayer()->map();
    foreach ($map->getBonuses() as $uid => $bonus) {
      if (!$map->hasBuildingAtPos($map->getHexFromId($uid))) {
        return;
      }
    }

    $this->scoreConservation(1);
  }
}
