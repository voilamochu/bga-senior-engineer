<?php
namespace ARK\Cards\Animals;

class A458_JapaneseMacaque extends \ARK\Models\Animal
{
  public function __construct($row)
  {
    parent::__construct($row);
    $this->id = 'A458_JapaneseMacaque';
    $this->number = 458;
    $this->name = clienttranslate('Japanese Macaque');
    $this->latin = clienttranslate('Macaca fuscata');
    $this->cost = 18;
    $this->appeal = 7;
    $this->enclosureRequirements = [
      WATER => 1,
    ];
    $this->enclosureSize = 3;
    $this->categories = [PRIMATE];
    $this->continents = [ASIA];
    $this->prerequisites = [
      ASIA => 1,
    ];
    $this->ability = [PILFERING => 2];
    $this->soloAbility = [SPRINT => 2];
  }
}
