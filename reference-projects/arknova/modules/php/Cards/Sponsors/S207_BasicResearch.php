<?php

namespace ARK\Cards\Sponsors;

class S207_BasicResearch extends \ARK\Models\Sponsor
{
  public function __construct($row)
  {
    parent::__construct($row);
    $this->id = 'S207_BasicResearch';
    $this->number = 207;
    $this->name = clienttranslate('Basic Research');
    $this->lvl = 4;
    $this->categories = [SCIENCE];
    $this->prerequisites = [UPGRADED_SPONSORS_CARD => 1, MAX_25_APPEAL => 1];
    $this->effects = [
      IMMEDIATE => [
        clienttranslate(
          'Total your different animal category and continent icons. For every 2 different icons you gain 1 conservation point and all other players gain 2 money.'
        ),
        clienttranslate(
          'Example: If you have a total of 7 different icons, you gain 3 conservation points and all other players gain 6 money each.'
        ),
      ],
    ];
  }

  public function getImmediate()
  {
    $icons = $this->getPlayer()->countCardIcons(true, \CONTINENTS_AND_TYPES);
    $n = intdiv(count(array_keys($icons)), 2);
    return $n == 0 ? [] : [[CONSERVATION => $n], [MONEY => 2 * $n, 'pId' => EVERYONE_ELSE]];
  }
}
