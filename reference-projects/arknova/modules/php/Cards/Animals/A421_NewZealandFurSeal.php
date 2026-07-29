<?php
namespace ARK\Cards\Animals;

class A421_NewZealandFurSeal extends \ARK\Models\Animal
{
  public function __construct($row)
  {
    parent::__construct($row);
    $this->id = 'A421_NewZealandFurSeal';
    $this->number = 421;
    $this->name = clienttranslate('New Zealand Fur Seal');
    $this->latin = clienttranslate('Arctocephalus forsteri');
    $this->cost = 17;
    $this->appeal = 8;
    $this->enclosureRequirements = [
      WATER => 1,
      ROCK => 1,
    ];
    $this->enclosureSize = 5;
    $this->categories = [PREDATOR];
    $this->continents = [AUSTRALIA];
    $this->prerequisites = [
      UPGRADED_ANIMALS_CARD => 1,
    ];
    $this->ability = [FULL_THROATED => null];
  }
}
