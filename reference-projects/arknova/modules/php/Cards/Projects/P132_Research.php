<?php
namespace ARK\Cards\Projects;

class P132_Research extends \ARK\Models\Projects\ProjectIcon
{
  public function __construct($row)
  {
    parent::__construct($row);
    $this->id = 'P132_Research';
    $this->number = 132;
    $this->name = clienttranslate('Research');
    $this->desc = clienttranslate('Requires **research** icons in your zoo.');
    $this->icon = SCIENCE;
    $this->slots = [
      [
        'condition' => 5,
        'gain' => [CONSERVATION => 4],
      ],
      [
        'condition' => 4,
        'gain' => [CONSERVATION => 3],
      ],
      [
        'condition' => 2,
        'gain' => [CONSERVATION => 2],
      ],
    ];
  }
}
