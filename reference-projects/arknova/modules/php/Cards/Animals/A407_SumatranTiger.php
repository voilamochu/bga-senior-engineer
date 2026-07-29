<?php
namespace ARK\Cards\Animals;

class A407_SumatranTiger extends \ARK\Models\Animal
{
  public function __construct($row)
  {
    parent::__construct($row);
    $this->id = 'A407_SumatranTiger';
    $this->number = 407;
    $this->name = clienttranslate('Sumatran Tiger');
    $this->latin = clienttranslate('Panthera tigris sumatrae - Critically Endangered');
    $this->cost = 26;
    $this->appeal = 8;
    $this->conservation = 2;
    $this->reputation = 1;
    $this->enclosureRequirements = [WATER => 2];
    $this->enclosureSize = 4;
    $this->categories = [PREDATOR];
    $this->continents = [ASIA];
    $this->prerequisites = [SCIENCE => 2];
  }
}
