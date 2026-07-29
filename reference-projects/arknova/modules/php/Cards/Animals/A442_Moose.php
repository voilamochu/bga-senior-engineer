<?php
namespace ARK\Cards\Animals;

class A442_Moose extends \ARK\Models\Animal
{
  public function __construct($row)
  {
    parent::__construct($row);
    $this->id = 'A442_Moose';
    $this->number = 442;
    $this->name = clienttranslate('Moose');
    $this->latin = clienttranslate('Alces alces');
    $this->cost = 19;
    $this->appeal = 7;
    $this->enclosureSize = 4;
    $this->categories = [HERBIVORE];
    $this->continents = [EUROPE];
    $this->prerequisites = [
      HERBIVORE => 2,
    ];
    $this->ability = [MULTIPLIER => SPONSORS, FLOCK_ANIMAL => 4];
  }
}
