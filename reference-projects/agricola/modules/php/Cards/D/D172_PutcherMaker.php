<?php
namespace AGR\Cards\D;

class D172_PutcherMaker extends \AGR\Models\Occupation
{
  public function __construct($row)
  {
    parent::__construct($row);
    $this->id = 'D172_PutcherMaker';
    $this->name = ('Putcher Maker');
    $this->deck = 'D';
    $this->number = 172;
    $this->category = FOOD_PROVIDER;
    $this->desc = [('At any time, you can exchange 1 reed for 2 food.')];
    $this->players = '5+';
    $this->implemented = false;
    $this->isCorbariusOrDulcinaria = true;
  }
}
