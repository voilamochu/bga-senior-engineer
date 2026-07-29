<?php
namespace AGR\Cards\C;

class C176_Cleanacre extends \AGR\Models\Occupation
{
  public function __construct($row)
  {
    parent::__construct($row);
    $this->id = 'C176_Cleanacre';
    $this->name = ('Cleanacre');
    $this->deck = 'C';
    $this->number = 176;
    $this->category = BUILDING_RESOURCE_PROVIDER;
    $this->desc = [
      (
        'Each time you use the "Farmland", "Cultivation", or "Farming Supplies" action (the latter only being available with 6 players), you also get 2 clay.'
      ),
    ];
    $this->players = '5+';
    $this->implemented = false;
  }
}
