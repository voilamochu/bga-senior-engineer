<?php
namespace ARK\Cards\Animals;

class A518_LesserBirdofparadise extends \ARK\Models\Animal
{
  public function __construct($row)
  {
    parent::__construct($row);
    $this->id = 'A518_LesserBirdofparadise';
    $this->number = 518;
    $this->name = clienttranslate('Lesser Bird-of-paradise');
    $this->latin = clienttranslate('Paradisaea minor');
    $this->cost = 15;
    $this->appeal = 5;
    $this->enclosureSize = 1;
    $this->categories = [BIRD];
    $this->continents = [AUSTRALIA];
    $this->ability = [POSTURING => 1];
  }
}
