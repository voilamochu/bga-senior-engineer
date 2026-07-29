<?php
namespace AGR\Cards\B;

class B177_StoneClawer extends \AGR\Models\Occupation
{
  public function __construct($row)
  {
    parent::__construct($row);
    $this->id = 'B177_StoneClawer';
    $this->name = ('Stone Clawer');
    $this->deck = 'B';
    $this->number = 177;
    $this->category = BUILDING_RESOURCE_PROVIDER;
    $this->desc = [('Each time you plow at least 1 field, you also get 1 stone.')];
    $this->players = '5+';
    $this->implemented = false;
  }
}
