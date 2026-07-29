<?php
namespace AGR\Cards\D;

class D85_Reader extends \AGR\Models\Occupation
{
  public function __construct($row)
  {
    parent::__construct($row);
    $this->id = 'D85_Reader';
    $this->name = clienttranslate('Reader');
    $this->deck = 'D';
    $this->number = 85;
    $this->category = FARM_PLANNER;
    $this->desc = [
      clienttranslate(
        'As soon as you have 6 (__7 in draft mode__) occupations in front of you (including this one), this card provides room for one person.'
      ),
    ];
    $this->players = '1+';
    $this->holder = true;
    $this->isCorbariusOrDulcinaria = true;
  }
}