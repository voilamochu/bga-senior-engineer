<?php
namespace ARK\Cards\Animals;

class A435_MalayanTapir extends \ARK\Models\Animal
{
  public function __construct($row)
  {
    parent::__construct($row);
    $this->id = 'A435_MalayanTapir';
    $this->number = 435;
    $this->name = clienttranslate('Malayan Tapir');
    $this->latin = clienttranslate('Tapirus indicus - Endangered');
    $this->cost = 17;
    $this->appeal = 5;
    $this->reputation = 1;
    $this->enclosureSize = 2;
    $this->categories = [HERBIVORE];
    $this->continents = [ASIA];
    $this->ability = [DIGGING => 3];
  }
}
