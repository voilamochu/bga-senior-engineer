<?php
namespace AGR\Cards\A;

class A175_HollowGardener extends \AGR\Models\Occupation
{
  public function __construct($row)
  {
    parent::__construct($row);
    $this->id = 'A175_HollowGardener';
    $this->name = ('Hollow Gardener');
    $this->deck = 'A';
    $this->number = 175;
    $this->category = CROP_PROVIDER;
    $this->desc = [
      (
        'Each time you take at least 3 clay from the "Hollow" accumulation space, you also get 1 grain. If you take at least 6 clay from it, you also get 1 vegetable (instead of grain).'
      ),
    ];
    $this->players = '5+';
    $this->implemented = false;
  }
}
