<?php
namespace AGR\Cards\C;

class C171_YoungArtist extends \AGR\Models\Occupation
{
  public function __construct($row)
  {
    parent::__construct($row);
    $this->id = 'C171_YoungArtist';
    $this->name = ('Young Artist');
    $this->deck = 'C';
    $this->number = 171;
    $this->category = ACTIONS_BOOSTER;
    $this->desc = [
      (
        'In the returning home phase of each round, you can pay 1 food to either take a "Minor Improvement" action or to draw 2 new minor improvements.'
      ),
    ];
    $this->players = '5+';
    $this->implemented = false;
  }
}
