<?php
namespace ARK\Cards\Animals;

class A427_WhiteRhinoceros extends \ARK\Models\Animal
{
  public function __construct($row)
  {
    parent::__construct($row);
    $this->id = 'A427_WhiteRhinoceros';
    $this->number = 427;
    $this->name = clienttranslate('White Rhinoceros');
    $this->latin = clienttranslate('Ceratotherium simum - Near Threatened');
    $this->cost = 24;
    $this->appeal = 9;
    $this->enclosureRequirements = [ROCK => 1];
    $this->enclosureSize = 4;
    $this->categories = [HERBIVORE];
    $this->continents = [AFRICA];
    $this->prerequisites = [SCIENCE => 1];
    $this->ability = [ASSERTION => null];
  }
}
