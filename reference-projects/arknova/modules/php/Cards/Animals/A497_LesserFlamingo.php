<?php
namespace ARK\Cards\Animals;

class A497_LesserFlamingo extends \ARK\Models\Animal
{
  public function __construct($row)
  {
    parent::__construct($row);
    $this->id = 'A497_LesserFlamingo';
    $this->number = 497;
    $this->name = clienttranslate('Lesser Flamingo');
    $this->latin = clienttranslate('Phoeniconaias minor - Near Threatened');
    $this->cost = 15;
    $this->appeal = 6;
    $this->enclosureRequirements = [
      WATER => 1,
    ];
    $this->enclosureSize = 2;
    $this->categories = [BIRD];
    $this->continents = [AFRICA];
    $this->ability = [POSTURING => 1];
  }
}
