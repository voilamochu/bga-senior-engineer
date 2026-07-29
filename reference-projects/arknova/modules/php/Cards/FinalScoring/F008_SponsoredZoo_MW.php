<?php

namespace ARK\Cards\FinalScoring;

class F008_SponsoredZoo_MW extends F008_SponsoredZoo
{
  public function __construct($row)
  {
    parent::__construct($row);
    $this->supported = [MW];
    $this->id = 'F008_SponsoredZoo_MW';
    $this->asset = "F008_SponsoredZoo";
    $this->scoreMap = [3 => 1, 5 => 2, 7 => 3, 9 => 4];
  }
}
