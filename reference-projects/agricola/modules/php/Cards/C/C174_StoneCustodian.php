<?php
namespace AGR\Cards\C;

class C174_StoneCustodian extends \AGR\Models\Occupation
{
  public function __construct($row)
  {
    parent::__construct($row);
    $this->id = 'C174_StoneCustodian';
    $this->name = ('Stone Custodian');
    $this->deck = 'C';
    $this->number = 174;
    $this->category = CROP_PROVIDER;
    $this->desc = [
      (
        'At the end of each work phase, if 1 stone accumulation space has stone left, you get 1 grain If 2 stone accumulation spaces have stone left, you get 1 vegetable instead.'
      ),
    ];
    $this->players = '5+';
    $this->implemented = false;
  }
}
