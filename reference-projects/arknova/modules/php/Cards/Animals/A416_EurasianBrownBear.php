<?php
namespace ARK\Cards\Animals;

class A416_EurasianBrownBear extends \ARK\Models\Animal
{
  public function __construct($row)
  {
    parent::__construct($row);
    $this->id = 'A416_EurasianBrownBear';
    $this->number = 416;
    $this->name = clienttranslate('Eurasian Brown Bear');
    $this->latin = clienttranslate('Ursus arctos arctos');
    $this->cost = 20;
    $this->appeal = 8;
    $this->enclosureRequirements = [
      WATER => 1,
    ];
    $this->enclosureSize = 5;
    $this->categories = [PREDATOR, BEAR];
    $this->continents = [EUROPE];
    $this->prerequisites = [
      BEAR => 1,
      UPGRADED_ANIMALS_CARD => 1,
    ];
    $this->ability = [MULTIPLIER => ASSOCIATION, FULL_THROATED => null];
  }
}
