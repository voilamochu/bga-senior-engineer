<?php

namespace ARK\Cards\Animals;

class A452_SenegalBushbaby extends \ARK\Models\Animal
{
  public function __construct($row)
  {
    parent::__construct($row);
    $this->id = 'A452_SenegalBushbaby';
    $this->number = 452;
    $this->name = clienttranslate('Senegal Bushbaby');
    $this->latin = clienttranslate('Galago senegalensis');
    $this->cost = 10;
    $this->appeal = 1;
    $this->enclosureSize = 1;
    $this->categories = [PRIMATE];
    $this->continents = [AFRICA];
    $this->prerequisites = [
      AFRICA => 2,
    ];
    $this->ability = [ICONIC_ANIMAL => AFRICA];
  }
}
