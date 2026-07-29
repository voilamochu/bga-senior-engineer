<?php
namespace AGR\Cards\A;

class A179_MountainShepherd extends \AGR\Models\Occupation
{
  public function __construct($row)
  {
    parent::__construct($row);
    $this->id = 'A179_MountainShepherd';
    $this->name = ('Mountain Shepherd');
    $this->deck = 'A';
    $this->number = 179;
    $this->category = LIVESTOCK_PROVIDER;
    $this->desc = [
      (
        'Each time you use either "Quarry" accumulation space, you immediately get an additional 1 sheep from the general supply.'
      ),
    ];
    $this->players = '5+';
    $this->implemented = false;
  }
}
