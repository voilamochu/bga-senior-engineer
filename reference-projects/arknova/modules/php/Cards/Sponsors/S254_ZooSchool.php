<?php

namespace ARK\Cards\Sponsors;

class S254_ZooSchool extends UniqueBuildingSponsor
{
  public function __construct($row)
  {
    parent::__construct($row);
    $this->id = 'S254_ZooSchool';
    $this->number = 254;
    $this->name = clienttranslate('Zoo School');
    $this->lvl = 5;
    $this->conservation = 1;
    $this->reputation = 1;
    $this->enclosure = 'zoo-school';
    $this->effects = [
      IMMEDIATE => [
        clienttranslate('Gain 1 reputation and 1 conservation point when you play this card.'),
        \clienttranslate(
          'Place the Zoo School unique building on your zoo map covering at least 2 border spaces. Take 1 card in reputation range or draw 1 card from the deck.'
        ),
      ],
    ];
  }

  public function getImmediate()
  {
    return [[BUILD => $this->enclosure], [\TAKE_IN_RANGE_OR_DECK => 1]];
  }
}
