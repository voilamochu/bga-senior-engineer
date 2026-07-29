<?php
namespace ARK\Cards\Animals;

class A412_Jaguar extends \ARK\Models\Animal
{
  public function __construct($row)
  {
    parent::__construct($row);
    $this->id = 'A412_Jaguar';
    $this->number = 412;
    $this->name = clienttranslate('Jaguar');
    $this->latin = clienttranslate('Panthera onca - Near Threatened');
    $this->cost = 16;
    $this->appeal = 8;
    $this->enclosureSize = 4;
    $this->categories = [PREDATOR];
    $this->continents = [AMERICAS];
    $this->prerequisites = [
      AMERICAS => 1,
    ];
    $this->ability = [HUNTER => 4];
  }
}
