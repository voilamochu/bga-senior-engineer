<?php
namespace AGR\Cards\A;

class A174_MasterHora extends \AGR\Models\Occupation
{
  public function __construct($row)
  {
    parent::__construct($row);
    $this->id = 'A174_MasterHora';
    $this->name = ('Master Hora');
    $this->deck = 'A';
    $this->number = 174;
    $this->category = CROP_PROVIDER;
    $this->desc = [
      (
        'Immediately before each time you place a person on an action space with the (meeple) symbol on the game board extension, you can buy 1 vegetable for 1 food.'
      ),
    ];
    $this->players = '5+';
    $this->implemented = false;
  }
}
