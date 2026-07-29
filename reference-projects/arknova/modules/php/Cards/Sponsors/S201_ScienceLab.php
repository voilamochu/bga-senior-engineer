<?php

namespace ARK\Cards\Sponsors;

class S201_ScienceLab extends \ARK\Models\Sponsor
{
  public function __construct($row)
  {
    parent::__construct($row);
    $this->id = 'S201_ScienceLab';
    $this->number = 201;
    $this->name = clienttranslate('Science Lab');
    $this->lvl = 5;
    $this->categories = [SCIENCE];
    $this->prerequisites = [UPGRADED_SPONSORS_CARD => 1];
    $this->effects = [
      IMMEDIATE => [clienttranslate('Take 1 card from the deck or in reputation range')],
      INCOME => [clienttranslate('Take 1 card from the deck or in reputation range')],
      ENDGAME => [clienttranslate('Gain 1 conservation point for 3-5 research icons in your zoo; gain 2 for 6 or more.')],
    ];
  }

  public function getImmediate()
  {
    return [[TAKE_IN_RANGE_OR_DECK => 1]];
  }

  public function getIncome()
  {
    return [[TAKE_IN_RANGE_OR_DECK => 1]];
  }

  public function score()
  {
    $n = $this->countIcon(SCIENCE);
    $this->scoreConservation($n, [3 => 1, 6 => 2]);
  }
}
