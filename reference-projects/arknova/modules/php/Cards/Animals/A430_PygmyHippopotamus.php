<?php
namespace ARK\Cards\Animals;

class A430_PygmyHippopotamus extends \ARK\Models\Animal
{
  public function __construct($row)
  {
    parent::__construct($row);
    $this->id = 'A430_PygmyHippopotamus';
    $this->number = 430;
    $this->name = clienttranslate('Pygmy Hippopotamus');
    $this->latin = clienttranslate('Choeropsis liberiensis - Endangered');
    $this->cost = 15;
    $this->appeal = 6;
    $this->enclosureRequirements = [
      WATER => 1,
    ];
    $this->enclosureSize = 2;
    $this->categories = [HERBIVORE];
    $this->continents = [AFRICA];
    $this->prerequisites = [
      PARTNER_ZOO => 1,
    ];
    $this->ability = [ACTION => SPONSORS];
  }
}
