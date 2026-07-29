<?php
namespace ARK\Cards\Animals;

class A514_Emu extends \ARK\Models\Animal
{
  public function __construct($row)
  {
    parent::__construct($row);
    $this->id = 'A514_Emu';
    $this->number = 514;
    $this->name = clienttranslate('Emu');
    $this->latin = clienttranslate('Dromaius novaehollandiae');
    $this->cost = 22;
    $this->appeal = 7;
    $this->enclosureSize = 5;
    $this->categories = [BIRD];
    $this->continents = [AUSTRALIA];
    $this->prerequisites = [
      AUSTRALIA => 2,
    ];
    $this->ability = [PEACOCKING => null];
  }
}
