<?php
namespace ARK\Cards\Animals;

class A439_Llama extends \ARK\Models\Animal
{
  public function __construct($row)
  {
    parent::__construct($row);
    $this->id = 'A439_Llama';
    $this->number = 439;
    $this->name = clienttranslate('Llama');
    $this->latin = clienttranslate('Lama glama');
    $this->cost = 10;
    $this->appeal = 4;
    $this->enclosureSize = 2;
    $this->categories = [HERBIVORE];
    $this->continents = [AMERICAS];
    $this->ability = [FLOCK_ANIMAL => 2];
  }
}
