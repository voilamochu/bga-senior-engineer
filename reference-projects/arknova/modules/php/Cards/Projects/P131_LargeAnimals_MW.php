<?php

namespace ARK\Cards\Projects;

class P131_LargeAnimals_MW extends P131_LargeAnimals
{
  public function __construct($row)
  {
    parent::__construct($row);
    $this->supported = [MW];
    $this->id = 'P131_LargeAnimals_MW';
    $this->asset = 'P131_LargeAnimals';
    $this->slots = [
      [
        'condition' => 3,
        'gain' => [CONSERVATION => 4],
      ],
      [
        'condition' => 2,
        'gain' => [CONSERVATION => 3],
      ],
      [
        'condition' => 1,
        'gain' => [CONSERVATION => 2],
      ],
    ];
  }
}
