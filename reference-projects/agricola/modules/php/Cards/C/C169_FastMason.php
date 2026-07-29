<?php
namespace AGR\Cards\C;

class C169_FastMason extends \AGR\Models\Occupation
{
  public function __construct($row)
  {
    parent::__construct($row);
    $this->id = 'C169_FastMason';
    $this->name = ('Fast Mason');
    $this->deck = 'C';
    $this->number = 169;
    $this->category = FARM_PLANNER;
    $this->desc = [
      (
        'Immediately after each time you use a clay/stone accumulation space, you can renovate your house to clay/stone without paying reed.'
      ),
    ];
    $this->players = '5+';
    $this->implemented = false;
  }
}
