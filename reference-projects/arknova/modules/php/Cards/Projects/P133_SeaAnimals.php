<?php

namespace ARK\Cards\Projects;

class P133_SeaAnimals extends \ARK\Models\Projects\ProjectIcon
{
  public function __construct($row)
  {
    parent::__construct($row);
    $this->supported = [MW];
    $this->type = \CARD_BASE_PROJECT;
    $this->id = 'P133_SeaAnimals';
    $this->number = 133;
    $this->name = clienttranslate('Sea Animals');
    $this->desc = clienttranslate('Requires **sea animals** icons in your zoo.');
    $this->icon = SEA_ANIMAL;
    $this->slots = [
      [
        'condition' => 5,
        'gain' => [CONSERVATION => 5],
      ],
      [
        'condition' => 4,
        'gain' => [CONSERVATION => 4],
      ],
      [
        'condition' => 2,
        'gain' => [CONSERVATION => 2],
      ],
    ];
  }
}
