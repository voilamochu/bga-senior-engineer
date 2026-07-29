<?php

namespace ARK\Cards\FinalScoring;

class F003_ResearchZoo extends \ARK\Models\FinalScoring
{
  public function __construct($row)
  {
    parent::__construct($row);
    $this->supported = [BASE];
    $this->id = 'F003_ResearchZoo';
    $this->number = 3;
    $this->name = clienttranslate('Research Zoo');
    $this->icon = SCIENCE;
    $this->desc = clienttranslate('Gain <CONSERVATION> for **research icons** in your zoo');
    $this->scoreMap = [3 => 1, 4 => 2, 5 => 3, 6 => 4];
  }

  public function getQuantity()
  {
    return $this->getPlayer()->countCardIcon(SCIENCE);
  }
}
