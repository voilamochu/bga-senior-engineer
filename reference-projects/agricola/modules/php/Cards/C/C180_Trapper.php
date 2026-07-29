<?php
namespace AGR\Cards\C;

class C180_Trapper extends \AGR\Models\Occupation
{
  public function __construct($row)
  {
    parent::__construct($row);
    $this->id = 'C180_Trapper';
    $this->name = ('Trapper');
    $this->deck = 'C';
    $this->number = 180;
    $this->category = LIVESTOCK_PROVIDER;
    $this->desc = [
      (
        'Each time after you use a wood accumulation space, if this is the 2nd/3rd/4th occupied wood accumulation space that round, you can buy 1 sheep/wild boar/cattle for 1 food.'
      ),
    ];
    $this->players = '5+';
    $this->implemented = false;
  }
}
