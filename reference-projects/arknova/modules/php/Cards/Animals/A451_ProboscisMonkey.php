<?php
namespace ARK\Cards\Animals;

class A451_ProboscisMonkey extends \ARK\Models\Animal
{
  public function __construct($row)
  {
    parent::__construct($row);
    $this->id = 'A451_ProboscisMonkey';
    $this->number = 451;
    $this->name = clienttranslate('Proboscis Monkey');
    $this->latin = clienttranslate('Nasalis larvatus - Endangered');
    $this->cost = 32;
    $this->appeal = 10;
    $this->conservation = 2;
    $this->enclosureRequirements = [
      ROCK => 1,
    ];
    $this->enclosureSize = 5;
    $this->categories = [PRIMATE];
    $this->continents = [ASIA];
    $this->prerequisites = [
      PRIMATE => 2,
      UPGRADED_ANIMALS_CARD => 1,
    ];
    $this->ability = [DOMINANCE => null];
  }
}
