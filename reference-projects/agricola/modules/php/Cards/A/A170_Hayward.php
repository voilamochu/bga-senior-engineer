<?php
namespace AGR\Cards\A;

class A170_Hayward extends \AGR\Models\Occupation
{
  public function __construct($row)
  {
    parent::__construct($row);
    $this->id = 'A170_Hayward';
    $this->name = ('Hayward');
    $this->deck = 'A';
    $this->number = 170;
    $this->category = FARM_PLANNER;
    $this->desc = [
      (
        'You can build fences at any time without placing a person. (This is not considered a "Build Fences" action.)'
      ),
    ];
    $this->players = '5+';
    $this->implemented = false;
  }
}
