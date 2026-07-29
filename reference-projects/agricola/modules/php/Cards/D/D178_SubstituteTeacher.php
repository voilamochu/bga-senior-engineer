<?php
namespace AGR\Cards\D;

class D178_SubstituteTeacher extends \AGR\Models\Occupation
{
  public function __construct($row)
  {
    parent::__construct($row);
    $this->id = 'D178_SubstituteTeacher';
    $this->name = ('Substitute Teacher');
    $this->deck = 'D';
    $this->number = 178;
    $this->category = BUILDING_RESOURCE_PROVIDER;
    $this->desc = [
      (
        'Each time all three "Lessons" action spaces are occupied, you can use this card with a person to get your choice of 1 building resource or 1 crop of each type.'
      ),
    ];
    $this->players = '5+';
    $this->implemented = false;
  }
}
