<?php
namespace ARK\Cards\Animals;

class A440_MountainTapir extends \ARK\Models\Animal
{
  public function __construct($row)
  {
    parent::__construct($row);
    $this->id = 'A440_MountainTapir';
    $this->number = 440;
    $this->name = clienttranslate('Mountain Tapir');
    $this->latin = clienttranslate('Tapirus pinchaque - Endangered');
    $this->cost = 15;
    $this->appeal = 4;
    $this->conservation = 1;
    $this->enclosureRequirements = [
      ROCK => 1,
    ];
    $this->enclosureSize = 2;
    $this->categories = [HERBIVORE];
    $this->continents = [AMERICAS];
    $this->ability = [DIGGING => 2];
  }
}
