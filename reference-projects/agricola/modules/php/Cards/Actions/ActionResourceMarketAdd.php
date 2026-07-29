<?php
namespace AGR\Cards\Actions;
use AGR\Managers\Farmers;

class ActionResourceMarketAdd extends \AGR\Models\ActionCard
{
  public function __construct($row)
  {
    parent::__construct($row);
    $this->id = 'ActionResourceMarketAdd';
    $this->name = clienttranslate('Resource Market');
    $this->desc = ['+1<STONE>+1<FOOD>'];
    $this->tooltip = [clienttranslate('Gain 1 stone and 1 food')];
    $this->container = 'add';

    $this->isAdditional = true;
    $this->players = [2];
    $this->flow = [
      'action' => GAIN,
      'args' => [FOOD => 1, STONE => 1],
    ];
  }
}
