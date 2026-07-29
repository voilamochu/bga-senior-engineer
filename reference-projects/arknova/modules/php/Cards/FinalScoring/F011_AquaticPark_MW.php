<?php

namespace ARK\Cards\FinalScoring;

class F011_AquaticPark_MW extends F011_AquaticPark
{
  public function __construct($row)
  {
    parent::__construct($row);
    $this->supported = [MW];
    $this->id = 'F011_AquaticPark_MW';
    $this->asset = "F011_AquaticPark";
    $this->scoreMap = [2 => 1, 4 => 2, 6 => 3, 7 => 4];
  }
}
