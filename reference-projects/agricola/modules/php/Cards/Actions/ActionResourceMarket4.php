<?php
namespace AGR\Cards\Actions;

class ActionResourceMarket4 extends \AGR\Models\ActionCard
{
  public function __construct($row)
  {
    parent::__construct($row);
    $this->id = 'ActionResourceMarket4';
    $this->actionCardType = 'ResourceMarket';
    $this->name = clienttranslate('Resource Market');
    $this->desc = ['+1<REED>+1<STONE>+1<FOOD>'];
    $this->tooltip = [clienttranslate('Gain 1 reed, 1 stone and 1 food')];
    $this->container = 'left';

    $this->players = [4];
    $this->flow = [
      'action' => GAIN,
      'args' => [REED => 1, FOOD => 1, STONE => 1],
    ];
  }
}
