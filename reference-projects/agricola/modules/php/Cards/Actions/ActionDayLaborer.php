<?php
namespace AGR\Cards\Actions;

class ActionDayLaborer extends \AGR\Models\ActionCard
{
  public function __construct($row)
  {
    parent::__construct($row);
    $this->id = 'ActionDayLaborer';
    $this->name = clienttranslate('Day Laborer');
    $this->desc = ['+2<FOOD>'];
    $this->tooltip = [clienttranslate('Gain 2 food')];

    $this->flow = [
      'action' => GAIN,
      'args' => [FOOD => 2],
    ];
    $this->accumulate = 'left'; // Useful for E148_Lazybones
    $this->skipGains = true;
  }
}
