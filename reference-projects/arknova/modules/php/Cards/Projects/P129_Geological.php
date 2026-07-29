<?php

namespace ARK\Cards\Projects;

class P129_Geological extends \ARK\Models\Projects\ProjectIcon
{
  public function __construct($row)
  {
    parent::__construct($row);
    $this->id = 'P129_Geological';
    $this->number = 129;
    $this->name = clienttranslate('Geological');
    $this->desc = clienttranslate('Requires **rock** icons in your zoo.');
    $this->icon = ROCK;
    $this->slots = [
      [
        'condition' => 5,
        'gain' => [CONSERVATION => 4],
      ],
      [
        'condition' => 3,
        'gain' => [CONSERVATION => 3],
      ],
      [
        'condition' => 2,
        'gain' => [CONSERVATION => 2],
      ],
    ];
  }
}
