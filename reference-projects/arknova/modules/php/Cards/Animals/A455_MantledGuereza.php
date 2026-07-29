<?php
namespace ARK\Cards\Animals;

class A455_MantledGuereza extends \ARK\Models\Animal
{
  public function __construct($row)
  {
    parent::__construct($row);
    $this->id = 'A455_MantledGuereza';
    $this->number = 455;
    $this->name = clienttranslate('Mantled Guereza');
    $this->latin = clienttranslate('Colobus guereza');
    $this->cost = 13;
    $this->appeal = 6;
    $this->enclosureSize = 3;
    $this->categories = [PRIMATE];
    $this->continents = [AFRICA];
    $this->ability = [CLEVER => null];
  }
}
