<?php
namespace AGR\Cards\D;

class D2_DwellingPlan extends \AGR\Models\MinorImprovement
{
  public function __construct($row)
  {
    parent::__construct($row);
    $this->id = 'D2_DwellingPlan';
    $this->name = clienttranslate('Dwelling Plan');
    $this->deck = 'D';
    $this->number = 2;
    $this->category = FARM_PLANNER;
    $this->desc = [clienttranslate('You can immediately take a __Renovation__ action.')];
    $this->passing = true;
    $this->cost = [
      FOOD => 1,
    ];
    $this->isCorbariusOrDulcinaria = true;
  }
  
  public function onBuy($player)
  {
    return [
      'type' => NODE_SEQ,
      'optional' => true,
      'childs' => [
        [
          'action' => RENOVATION,
          'pId' => $this->pId,
        ],
      ]
    ];
  }
}
