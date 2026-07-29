<?php

namespace ARK\Cards\FinalScoring;

class F001_LargeAnimalZoo_MW extends F001_LargeAnimalZoo
{
  public function __construct($row)
  {
    parent::__construct($row);
    $this->supported = [MW];
    $this->id = 'F001_LargeAnimalZoo_MW';
    $this->asset = "F001_LargeAnimalZoo";
    $this->scoreMap = [1 => 1, 2 => 2, 3 => 3, 4 => 4];
  }
}
