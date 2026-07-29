<?php

namespace ARK\Cards\FinalScoring;

class F005_ConservationZoo_MW extends F005_ConservationZoo
{
  public function __construct($row)
  {
    parent::__construct($row);
    $this->supported = [MW];
    $this->id = 'F005_ConservationZoo_MW';
    $this->asset = "F005_ConservationZoo";
    $this->scoreMap = [2 => 1, 3 => 2, 4 => 3, 5 => 4];
  }
}
