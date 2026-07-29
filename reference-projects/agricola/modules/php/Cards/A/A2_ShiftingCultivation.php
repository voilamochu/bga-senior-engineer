<?php
namespace AGR\Cards\A;

class A2_ShiftingCultivation extends \AGR\Models\MinorImprovement
{
  public function __construct($row)
  {
    parent::__construct($row);
    $this->id = 'A2_ShiftingCultivation';
    $this->name = clienttranslate('Shifting Cultivation');
    $this->deck = 'A';
    $this->number = 2;
    $this->category = FARM_PLANNER;
    $this->desc = [clienttranslate('Immediately plow 1 field.')];
    $this->passing = true;
    $this->cost = [
      FOOD => 2,
    ];
  }

  public function onBuy($player)
  {
    return [
      'action' => PLOW,
      'optional' => true,
    ];
  }
}
