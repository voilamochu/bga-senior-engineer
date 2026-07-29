<?php
namespace AGR\Cards\D;

class D3_Furrows extends \AGR\Models\MinorImprovement
{
  public function __construct($row)
  {
    parent::__construct($row);
    $this->id = 'D3_Furrows';
    $this->name = clienttranslate('Furrows');
    $this->deck = 'D';
    $this->number = 3;
    $this->category = ACTIONS_BOOSTER;
    $this->desc = [clienttranslate('You can immediately sow in exactly 1 field.')];
    $this->passing = true;
    $this->isCorbariusOrDulcinaria = true;
  }
  
  public function onBuy($player)
  {
    return [
      'action' => SOW, 
      'optional' => true, 
      'args' => ['max' => 1]
    ];
  }
}
