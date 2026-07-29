<?php
namespace AGR\Cards\C;

class C177_MountainHiker extends \AGR\Models\Occupation
{
  public function __construct($row)
  {
    parent::__construct($row);
    $this->id = 'C177_MountainHiker';
    $this->name = ('Mountain Hiker');
    $this->deck = 'C';
    $this->number = 177;
    $this->category = BUILDING_RESOURCE_PROVIDER;
    $this->desc = [
      (
        'Immediately after each time you use an accumulation space on the game board extension, you can buy 1 stone for 1 food.'
      ),
    ];
    $this->players = '5+';
    $this->implemented = false;
  }
}
