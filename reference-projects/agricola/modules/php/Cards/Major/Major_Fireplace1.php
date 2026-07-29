<?php

namespace AGR\Cards\Major;

use AGR\Helpers\Utils;
use AGR\Models\MajorImprovement;

class Major_Fireplace1 extends MajorImprovement
{
  public function __construct($row)
  {
    parent::__construct($row);
    $this->id = 'Major_Fireplace1';
    $this->number = 1;
    $this->name = clienttranslate('Fireplace');
    $this->tooltip = [];
    $this->desc = [
      clienttranslate('[Anytime]'),
      '<VEGETABLE> <ARROW> 2<FOOD>      <PIG> <ARROW> 2<FOOD>',
      '<SHEEP> <ARROW> 2<FOOD>      <CATTLE> <ARROW> 3<FOOD>',
      clienttranslate('[__Bake Bread__ action:]'),
      '<GRAIN> <ARROW> 2<FOOD>',
    ];

    $this->cost = [CLAY => 2];
    $this->vp = 1;
    $this->isCookery = true;
    $this->isBakingImprovement = true;
    $this->exchanges = [
      Utils::formatExchange([VEGETABLE => [FOOD => 2]], $this->name),
      Utils::formatExchange([PIG => [FOOD => 2]], $this->name),
      Utils::formatExchange([SHEEP => [FOOD => 2]], $this->name),
      Utils::formatExchange([CATTLE => [FOOD => 3]], $this->name),
      Utils::formatExchange([GRAIN => [FOOD => 2]], $this->name, [BREAD]),
    ];
  }
}
