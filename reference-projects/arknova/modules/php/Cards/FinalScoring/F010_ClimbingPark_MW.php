<?php

namespace ARK\Cards\FinalScoring;

class F010_ClimbingPark_MW extends F010_ClimbingPark
{
  public function __construct($row)
  {
    parent::__construct($row);
    $this->supported = [MW];
    $this->id = 'F010_ClimbingPark_MW';
    $this->asset = "F010_ClimbingPark";
    $this->scoreMap = [1 => 1, 3 => 2, 5 => 3, 6 => 4];
  }
}
