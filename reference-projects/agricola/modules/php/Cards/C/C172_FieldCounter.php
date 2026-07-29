<?php
namespace AGR\Cards\C;

class C172_FieldCounter extends \AGR\Models\Occupation
{
  public function __construct($row)
  {
    parent::__construct($row);
    $this->id = 'C172_FieldCounter';
    $this->name = ('Field Counter');
    $this->deck = 'C';
    $this->number = 172;
    $this->category = FOOD_PROVIDER;
    $this->desc = [
      (
        'Each time another player plows a field, place 1 food on this card. Once this game, you can turn this card face down to get the food on it.'
      ),
    ];
    $this->players = '5+';
    $this->implemented = false;
  }
}
