<?php

namespace ARK\Cards\Sponsors;

class S221_Archeologist extends \ARK\Models\Sponsor
{
  public function __construct($row)
  {
    parent::__construct($row);
    $this->id = 'S221_Archeologist';
    $this->number = 221;
    $this->name = clienttranslate('Archaeologist');
    $this->lvl = 4;
    $this->categories = [SCIENCE];
    $this->prerequisites = [
      SCIENCE => 1,
    ];
    $this->effects = [
      PASSIVE => [
        clienttranslate(
          'Every time you gain the placement bonus of a border space in your zoo, you gain an additional free placement bonus of your choice.'
        ),
        clienttranslate('Choose any placement bonus in your zoo that has not yet been covered.'),
        \clienttranslate('This bonus does not have to be on a border space.'),
        clienttranslate(
          'If you gain several placement bonuses on border spaces at the same time, you may take an additional free placement bonus for each one.'
        ),
        \clienttranslate('These can be any combination of the same or different bonuses.'),
        \clienttranslate('Take all placement bonuses in the order of your choice.'),
      ],
      ENDGAME => [
        clienttranslate(
          'Gain 1 conservation point if you have covered all the border spaces in your zoo (except for the rock and water spaces).'
        ),
      ],
    ];
    $this->person = true;
  }

  public function score()
  {
    $map = $this->getPlayer()->map();
    $n = $map->areBorderCellsCovered() ? 1 : 0;
    $this->scoreConservation($n);
  }
}
