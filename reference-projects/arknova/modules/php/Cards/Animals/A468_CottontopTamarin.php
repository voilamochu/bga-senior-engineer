<?php
namespace ARK\Cards\Animals;

class A468_CottontopTamarin extends \ARK\Models\Animal
{
  public function __construct($row)
  {
    parent::__construct($row);
    $this->id = 'A468_CottontopTamarin';
    $this->number = 468;
    $this->name = clienttranslate('Cotton-top Tamarin');
    $this->latin = clienttranslate('Saguinus oedipus - Critically Endangered');
    $this->cost = 15;
    $this->appeal = 4;
    $this->conservation = 1;
    $this->reputation = 1;
    $this->enclosureSize = 1;
    $this->categories = [PRIMATE];
    $this->continents = [AMERICAS];
    $this->prerequisites = [
      SCIENCE => 2,
    ];
  }
}
