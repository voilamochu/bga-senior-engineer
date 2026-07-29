<?php
namespace AGR\Cards\Actions;

class ActionVegetableSeeds extends \AGR\Models\ActionCard
{
  public function __construct($row)
  {
    parent::__construct($row);
    $this->id = 'ActionVegetableSeeds';
    $this->name = clienttranslate('Vegetable Seeds');
    $this->desc = ['+1<VEGETABLE>'];
    $this->tooltip = [clienttranslate('Gain 1 vegetable')];

    $this->stage = 3;
    $this->flow = [
      'action' => GAIN,
      'args' => [VEGETABLE => 1],
    ];
  }
}
