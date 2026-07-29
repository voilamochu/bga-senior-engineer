<?php

namespace ARK\Cards\FinalScoring;

class F003_ResearchZoo_MW extends F003_ResearchZoo
{
  public function __construct($row)
  {
    parent::__construct($row);
    $this->supported = [MW];
    $this->id = 'F003_ResearchZoo_MW';
    $this->asset = "F003_ResearchZoo";
    $this->scoreMap = [3 => 1, 4 => 2, 5 => 3, 7 => 4];
  }
}
