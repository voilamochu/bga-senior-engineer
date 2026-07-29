<?php
namespace AGR\Cards\Actions;
use AGR\Helpers\Utils;

class ActionMeetingPlace extends \AGR\Models\ActionCard
{
  public function __construct($row)
  {
    parent::__construct($row);
    $this->id = 'ActionMeetingPlace';
    $this->name = clienttranslate('Meeting place');
    $this->desc = ['<FIRST> + 1<MINOR>'];
    $this->tooltipDesc = ['<FIRST>', clienttranslate('and/or'), '1<MINOR>'];
    $this->tooltip = [
      clienttranslate(
        'From that moment, you are considered the “starting player” (even though another player started the current round).'
      ),
      clienttranslate('Additionally, you can play exactly one minor improvement from your hand'),
    ];

    $this->players = [2, 3, 4];
    $this->isNotBeginner = true;
    $this->flow = [
      'type' => NODE_SEQ,
      'childs' => [
        ['action' => FIRSTPLAYER],
        Utils::wrapOptional([
          'action' => IMPROVEMENT,
          'args' => [
            'types' => [MINOR],
          ],
        ]),
      ],
    ];
  }
}
