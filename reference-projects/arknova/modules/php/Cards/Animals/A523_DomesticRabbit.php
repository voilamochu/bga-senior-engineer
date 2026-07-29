<?php

namespace ARK\Cards\Animals;

class A523_DomesticRabbit extends \ARK\Models\Animal
{
  public function __construct($row)
  {
    parent::__construct($row);
    $this->id = 'A523_DomesticRabbit';
    $this->number = 523;
    $this->name = clienttranslate('Domestic Rabbit');
    $this->latin = clienttranslate('Oryctolagus cuniculus d. dom.');
    $this->cost = 7;
    $this->noRegularEnclosure = true;
    $this->specialEnclosure = [
      'types' => [PETTING_ZOO],
      'cubes' => 1,
    ];
    $this->categories = [PET];
    $this->continents = [];
    $this->ability = [PETTING_ZOO_ANIMAL => null];
  }
}
