<?php

namespace ARK\Cards\FinalScoring;

class F011_AquaticPark extends \ARK\Models\FinalScoring
{
  public function __construct($row)
  {
    parent::__construct($row);
    $this->supported = [BASE];
    $this->id = 'F011_AquaticPark';
    $this->number = 11;
    $this->name = clienttranslate('Aquatic Park');
    $this->icon = 'WATER';
    $this->desc = clienttranslate('Gain <CONSERVATION> for **water icons** in your zoo');
    $this->scoreMap = [2 => 1, 4 => 2, 6 => 3, 8 => 4];
  }

  public function getQuantity()
  {
    return $this->getPlayer()->countCardIcon(WATER);
  }
}
