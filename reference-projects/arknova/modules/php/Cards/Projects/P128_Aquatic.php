<?php
namespace ARK\Cards\Projects;

class P128_Aquatic extends \ARK\Models\Projects\ProjectIcon
{
  public function __construct($row)
  {
    parent::__construct($row);
    $this->id = 'P128_Aquatic';
    $this->number = 128;
    $this->name = clienttranslate('Aquatic');
    $this->desc = clienttranslate('Requires **water** icons in your zoo.');
    $this->icon = WATER;
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
