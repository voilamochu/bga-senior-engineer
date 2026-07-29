<?php
namespace AGR\Cards\Actions;

class ActionMeetingPlaceSoloBeginner extends \AGR\Models\ActionCard
{
  public function __construct($row)
  {
    parent::__construct($row);
    $this->id = 'ActionMeetingPlaceSoloBeginner';
    $this->name = clienttranslate('Meeting place');
    $this->actionCardType = 'MeetingPlace';
    $this->desc = ['1<FOOD>'];
    $this->tooltip = [clienttranslate('Accumulate 1 food each turn.')];
    $this->accumulate = 'right';

    $this->players = [1];
    $this->isBeginner = true;
    $this->accumulation = [FOOD => 1];
    $this->flow = [
      'action' => COLLECT,
    ];
  }
}
