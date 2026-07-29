<?php
namespace ARK\Cards\Animals;

class A507_GreaterRhea extends \ARK\Models\Animal
{
  public function __construct($row)
  {
    parent::__construct($row);
    $this->id = 'A507_GreaterRhea';
    $this->number = 507;
    $this->name = clienttranslate('Greater Rhea');
    $this->latin = clienttranslate('Rhea americana - Near Threatened');
    $this->cost = 12;
    $this->appeal = 5;
    $this->enclosureSize = 2;
    $this->categories = [BIRD];
    $this->continents = [AMERICAS];
    $this->ability = [SPRINT => 1];
  }
}
