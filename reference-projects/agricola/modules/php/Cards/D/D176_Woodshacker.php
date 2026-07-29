<?php
namespace AGR\Cards\D;

class D176_Woodshacker extends \AGR\Models\Occupation
{
  public function __construct($row)
  {
    parent::__construct($row);
    $this->id = 'D176_Woodshacker';
    $this->name = ('Woodshacker');
    $this->deck = 'D';
    $this->number = 176;
    $this->category = BUILDING_RESOURCE_PROVIDER;
    $this->desc = [
      (
        'In the work phase of each round, the first and the second time you use a wood accumulation space, you also get 1 and 2 clay respectively.'
      ),
    ];
    $this->players = '5+';
    $this->implemented = false;
  }
}
