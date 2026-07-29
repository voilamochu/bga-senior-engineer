<?php
namespace AGR\Cards\Actions;

class ActionMeetingPlaceBeginner extends \AGR\Models\ActionCard
{
  public function __construct($row)
  {
    parent::__construct($row);
    $this->id = 'ActionMeetingPlaceBeginner';
    $this->name = clienttranslate('Meeting place');
    $this->actionCardType = 'MeetingPlace';
    $this->desc = ['<FIRST> 1<FOOD>'];
    $this->tooltip = [
      clienttranslate(
        'From that moment, you are considered the “starting player” (even though another player started the current round).'
      ),
      clienttranslate('Accumulate 1 food each turn.'),
    ];
    $this->accumulate = 'right';

    $this->isBeginner = true;
    $this->players = [2, 3, 4];
    $this->accumulation = [FOOD => 1];
    $this->flow = [
      'type' => NODE_SEQ,
      'childs' => [['action' => FIRSTPLAYER], ['action' => COLLECT]],
    ];
  }
}
