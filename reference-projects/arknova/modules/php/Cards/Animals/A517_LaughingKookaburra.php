<?php
namespace ARK\Cards\Animals;

class A517_LaughingKookaburra extends \ARK\Models\Animal
{
  public function __construct($row)
  {
    parent::__construct($row);
    $this->id = 'A517_LaughingKookaburra';
    $this->number = 517;
    $this->name = clienttranslate('Laughing Kookaburra');
    $this->latin = clienttranslate('Dacelo novaeguineae');
    $this->cost = 9;
    $this->enclosureSize = 2;
    $this->categories = [BIRD];
    $this->continents = [AUSTRALIA];
    $this->prerequisites = [
      AUSTRALIA => 1,
    ];
    $this->ability = [ICONIC_ANIMAL => AUSTRALIA];
  }
}
