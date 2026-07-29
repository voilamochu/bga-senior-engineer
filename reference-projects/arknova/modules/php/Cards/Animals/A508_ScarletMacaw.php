<?php
namespace ARK\Cards\Animals;

class A508_ScarletMacaw extends \ARK\Models\Animal
{
  public function __construct($row)
  {
    parent::__construct($row);
    $this->id = 'A508_ScarletMacaw';
    $this->number = 508;
    $this->name = clienttranslate('Scarlet Macaw');
    $this->latin = clienttranslate('Ara macao');
    $this->cost = 16;
    $this->appeal = 4;
    $this->enclosureSize = 1;
    $this->categories = [BIRD];
    $this->continents = [AMERICAS];
    $this->prerequisites = [
      PARTNER_ZOO => 1,
    ];
    $this->ability = [POSTURING => 3];
  }
}
