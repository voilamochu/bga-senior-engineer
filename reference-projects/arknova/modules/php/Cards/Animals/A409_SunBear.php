<?php
namespace ARK\Cards\Animals;

class A409_SunBear extends \ARK\Models\Animal
{
  public function __construct($row)
  {
    parent::__construct($row);
    $this->id = 'A409_SunBear';
    $this->number = 409;
    $this->name = clienttranslate('Sun Bear');
    $this->latin = clienttranslate('Helarctos malayanus - Vulnerable');
    $this->cost = 16;
    $this->appeal = 5;
    $this->enclosureSize = 2;
    $this->categories = [PREDATOR, BEAR];
    $this->continents = [ASIA];
    $this->prerequisites = [
      PARTNER_ZOO => 1,
    ];
    $this->ability = [ACTION => ASSOCIATION];
  }
}
