<?php
namespace ARK\Cards\Animals;

class A511_GreaterFlamingo extends \ARK\Models\Animal
{
  public function __construct($row)
  {
    parent::__construct($row);
    $this->id = 'A511_GreaterFlamingo';
    $this->number = 511;
    $this->name = clienttranslate('Greater Flamingo');
    $this->latin = clienttranslate('Phoenicopterus roseus');
    $this->cost = 16;
    $this->appeal = 7;
    $this->enclosureRequirements = [
      WATER => 1,
    ];
    $this->enclosureSize = 3;
    $this->categories = [BIRD];
    $this->continents = [EUROPE];
    $this->ability = [POSTURING => 1];
  }
}
