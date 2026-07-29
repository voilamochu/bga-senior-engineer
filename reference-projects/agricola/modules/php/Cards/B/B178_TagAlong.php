<?php
namespace AGR\Cards\B;

class B178_TagAlong extends \AGR\Models\Occupation
{
  public function __construct($row)
  {
    parent::__construct($row);
    $this->id = 'B178_TagAlong';
    $this->name = ('Tag-Along');
    $this->deck = 'B';
    $this->number = 178;
    $this->category = BUILDING_RESOURCE_PROVIDER;
    $this->desc = [
      (
        'Immediately after each time another player uses the "Resource Market" action space, you can also place a person there to take the action as well.'
      ),
    ];
    $this->players = '5+';
    $this->implemented = false;
  }
}
