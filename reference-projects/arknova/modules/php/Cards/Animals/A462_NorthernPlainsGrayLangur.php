<?php
namespace ARK\Cards\Animals;

class A462_NorthernPlainsGrayLangur extends \ARK\Models\Animal
{
  public function __construct($row)
  {
    parent::__construct($row);
    $this->id = 'A462_NorthernPlainsGrayLangur';
    $this->number = 462;
    $this->name = clienttranslate('Northern Plains Gray Langur');
    $this->latin = clienttranslate('Semnopithecus entellus');
    $this->cost = 13;
    $this->appeal = 6;
    $this->enclosureRequirements = [
      ROCK => 1,
    ];
    $this->enclosureSize = 3;
    $this->categories = [PRIMATE];
    $this->continents = [ASIA];
    $this->ability = [JUMPING => 3];
  }
}
