<?php

namespace ARK\Cards\FinalScoring;

class F010_ClimbingPark extends \ARK\Models\FinalScoring
{
  public function __construct($row)
  {
    parent::__construct($row);
    $this->supported = [BASE];
    $this->id = 'F010_ClimbingPark';
    $this->number = 10;
    $this->name = clienttranslate('Climbing Park');
    $this->icon = 'ROCK';
    $this->desc = clienttranslate('Gain <CONSERVATION> for **rock icons** in your zoo');
    $this->scoreMap = [1 => 1, 3 => 2, 5 => 3, 7 => 4];
  }

  public function getQuantity()
  {
    return $this->getPlayer()->countCardIcon(ROCK);
  }
}
