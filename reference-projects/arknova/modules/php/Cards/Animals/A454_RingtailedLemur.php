<?php
namespace ARK\Cards\Animals;

class A454_RingtailedLemur extends \ARK\Models\Animal
{
  public function __construct($row)
  {
    parent::__construct($row);
    $this->id = 'A454_RingtailedLemur';
    $this->number = 454;
    $this->name = clienttranslate('Ring-tailed Lemur');
    $this->latin = clienttranslate('Lemur catta - Endangered');
    $this->cost = 12;
    $this->appeal = 6;
    $this->enclosureRequirements = [
      ROCK => 1,
    ];
    $this->enclosureSize = 3;
    $this->categories = [PRIMATE];
    $this->continents = [AFRICA];
    $this->ability = [SUNBATHING => 3];
  }
}
