<?php
namespace AGR\Cards\D;

class D177_Graduate extends \AGR\Models\Occupation
{
  public function __construct($row)
  {
    parent::__construct($row);
    $this->id = 'D177_Graduate';
    $this->name = ('Graduate');
    $this->deck = 'D';
    $this->number = 177;
    $this->category = BUILDING_RESOURCE_PROVIDER;
    $this->desc = [
      ('When you play this card you immediately pay 1 food. If you do, you get 2 stone and 2 reed.'),
    ];
    $this->players = '5+';
    $this->implemented = false;
  }
}
