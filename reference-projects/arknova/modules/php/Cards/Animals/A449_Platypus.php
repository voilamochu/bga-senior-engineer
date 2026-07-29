<?php
namespace ARK\Cards\Animals;

class A449_Platypus extends \ARK\Models\Animal
{
  public function __construct($row)
  {
    parent::__construct($row);
    $this->id = 'A449_Platypus';
    $this->number = 449;
    $this->name = clienttranslate('Platypus');
    $this->latin = clienttranslate('Ornithorhynchus anatinus - Near Threatened');
    $this->cost = 10;
    $this->appeal = 4;
    $this->enclosureRequirements = [
      WATER => 1,
    ];
    $this->enclosureSize = 2;
    $this->categories = [HERBIVORE];
    $this->continents = [AUSTRALIA];
    $this->ability = [VENOM => 1];
    $this->soloAbility = [INVENTIVE => 1];
  }
}
