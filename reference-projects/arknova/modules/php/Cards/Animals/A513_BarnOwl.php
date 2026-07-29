<?php
namespace ARK\Cards\Animals;

class A513_BarnOwl extends \ARK\Models\Animal
{
  public function __construct($row)
  {
    parent::__construct($row);
    $this->id = 'A513_BarnOwl';
    $this->number = 513;
    $this->name = clienttranslate('Barn Owl');
    $this->latin = clienttranslate('Tyto alba');
    $this->cost = 12;
    $this->appeal = 3;
    $this->enclosureSize = 1;
    $this->categories = [BIRD];
    $this->continents = [EUROPE];
    $this->ability = [PERCEPTION => 4];
  }
}
