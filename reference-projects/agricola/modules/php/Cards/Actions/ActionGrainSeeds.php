<?php
namespace AGR\Cards\Actions;

class ActionGrainSeeds extends \AGR\Models\ActionCard
{
  public function __construct($row)
  {
    parent::__construct($row);
    $this->id = 'ActionGrainSeeds';
    $this->name = clienttranslate('Grain Seeds');
    $this->desc = ['+1<GRAIN>'];
    $this->tooltip = [clienttranslate('Gain 1 grain')];

    $this->flow = [
      'action' => GAIN,
      'args' => [GRAIN => 1],
    ];    
    $this->accumulate = 'left'; // Useful for E148_Lazybones
    $this->skipGains = true;
  }
}
