<?php
namespace AGR\Cards\B;

class B175_FieldOverseer extends \AGR\Models\Occupation
{
  public function __construct($row)
  {
    parent::__construct($row);
    $this->id = 'B175_FieldOverseer';
    $this->name = ('Field Overseer');
    $this->deck = 'B';
    $this->number = 175;
    $this->category = CROP_PROVIDER;
    $this->desc = [
      (
        'Each time the other players harvest grain from at least 3/4/6 fields combined, you get 1 food/grain/vegetable.'
      ),
    ];
    $this->players = '5+';
    $this->implemented = false;
  }
}
