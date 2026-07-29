<?php
namespace ARK\Cards\Animals;

class A414_SouthAmericanCoati extends \ARK\Models\Animal
{
  public function __construct($row)
  {
    parent::__construct($row);
    $this->id = 'A414_SouthAmericanCoati';
    $this->number = 414;
    $this->name = clienttranslate('South American Coati');
    $this->latin = clienttranslate('Nasua nasua');
    $this->cost = 11;
    $this->appeal = 4;
    $this->enclosureRequirements = [
      ROCK => 1,
    ];
    $this->enclosureSize = 2;
    $this->categories = [PREDATOR, BEAR];
    $this->continents = [AMERICAS];
    $this->ability = [INVENTIVE => 1];
  }
}
