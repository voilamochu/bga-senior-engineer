<?php
namespace ARK\Cards\Animals;

class A453_CollaredMangabey extends \ARK\Models\Animal
{
  public function __construct($row)
  {
    parent::__construct($row);
    $this->id = 'A453_CollaredMangabey';
    $this->number = 453;
    $this->name = clienttranslate('Collared Mangabey');
    $this->latin = clienttranslate('Cercocebus torquatus - Endangered');
    $this->cost = 20;
    $this->appeal = 8;
    $this->enclosureSize = 4;
    $this->categories = [PRIMATE];
    $this->continents = [AFRICA];
    $this->prerequisites = [
      AFRICA => 1,
    ];
    $this->ability = [ACTION => CARDS];
  }
}
