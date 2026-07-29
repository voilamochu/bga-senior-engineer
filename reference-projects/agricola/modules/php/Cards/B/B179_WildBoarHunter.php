<?php
namespace AGR\Cards\B;

class B179_WildBoarHunter extends \AGR\Models\Occupation
{
  public function __construct($row)
  {
    parent::__construct($row);
    $this->id = 'B179_WildBoarHunter';
    $this->name = ('Wild Boar Hunter');
    $this->deck = 'B';
    $this->number = 179;
    $this->category = LIVESTOCK_PROVIDER;
    $this->desc = [
      (
        'In the returning home phase of each round, if at least 3 wood accumulation spaces are occupied, you can pay 1 wood to get 1 wild boar.'
      ),
    ];
    $this->players = '5+';
    $this->implemented = false;
  }
}
