<?php
namespace AGR\Cards\Actions;

class ActionMeetingPlaceSolo extends \AGR\Models\ActionCard
{
  public function __construct($row)
  {
    parent::__construct($row);
    $this->id = 'ActionMeetingPlaceSolo';
    $this->name = clienttranslate('Meeting place');
    $this->actionCardType = 'MeetingPlace';
    $this->desc = ['1<MINOR>'];
    $this->tooltipDesc = ['1<MINOR>'];
    $this->tooltip = [clienttranslate('You can play exactly one minor improvement from your hand')];

    $this->players = [1];
    $this->isNotBeginner = true;
    $this->flow = [
      'action' => IMPROVEMENT,
      'args' => [
        'types' => [MINOR],
      ],
    ];
  }
}
