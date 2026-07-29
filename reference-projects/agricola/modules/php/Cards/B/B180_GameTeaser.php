<?php
namespace AGR\Cards\B;

class B180_GameTeaser extends \AGR\Models\Occupation
{
  public function __construct($row)
  {
    parent::__construct($row);
    $this->id = 'B180_GameTeaser';
    $this->name = ('Game Teaser');
    $this->deck = 'B';
    $this->number = 180;
    $this->category = LIVESTOCK_PROVIDER;
    $this->desc = [
      (
        'Each time you take 1/2/3 food from a food accumulation space, you also get 1 cattle/wild boar/sheep.'
      ),
    ];
    $this->players = '5+';
    $this->implemented = false;
  }
}
