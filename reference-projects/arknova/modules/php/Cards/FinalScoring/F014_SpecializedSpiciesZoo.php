<?php

namespace ARK\Cards\FinalScoring;

class F014_SpecializedSpeciesZoo extends \ARK\Models\FinalScoring
{
  public function __construct($row)
  {
    parent::__construct($row);
    $this->supported = [MW];
    $this->id = 'F014_SpecializedSpeciesZoo';
    $this->number = 14;
    $this->name = clienttranslate('Specialized Species Zoo');
    $this->icon = 'ALL-ANIMALS';
    $this->desc = clienttranslate('Choose 1 **animal category icon** you did **not support a Base Conservation Project** with. Gain <CONSERVATION> for those icons.');
    $this->scoreMap = [3 => 1, 4 => 2, 5 => 3, 6 => 4];
  }

  public function getQuantity()
  {
    return 0; // TODO
    //    return $this->getPlayer()->countCardIcon(WATER);
  }
}
