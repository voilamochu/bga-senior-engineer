<?php
namespace ARK\Cards\Animals;

class A413_Cougar extends \ARK\Models\Animal
{
  public function __construct($row)
  {
    parent::__construct($row);
    $this->id = 'A413_Cougar';
    $this->number = 413;
    $this->name = clienttranslate('Cougar');
    $this->latin = clienttranslate('Puma concolor');
    $this->cost = 10;
    $this->appeal = 5;
    $this->enclosureRequirements = [
      ROCK => 1,
    ];
    $this->enclosureSize = 3;
    $this->categories = [PREDATOR];
    $this->continents = [AMERICAS];
    $this->ability = [JUMPING => 3];
  }
}
