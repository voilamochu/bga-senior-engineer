<?php
namespace AGR\Cards\B;

class B174_RiverbankGardener extends \AGR\Models\Occupation
{
  public function __construct($row)
  {
    parent::__construct($row);
    $this->id = 'B174_RiverbankGardener';
    $this->name = ('Riverbank Gardener');
    $this->deck = 'B';
    $this->number = 174;
    $this->category = CROP_PROVIDER;
    $this->desc = [
      ('Each time you use the "Riverbank Forest" accumulation space, you also get 1 vegetable.'),
    ];
    $this->players = '5+';
    $this->implemented = false;
  }
}
