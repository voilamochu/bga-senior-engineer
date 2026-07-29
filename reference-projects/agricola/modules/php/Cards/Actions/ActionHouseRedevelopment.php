<?php
namespace AGR\Cards\Actions;

use AGR\Helpers\Utils;

class ActionHouseRedevelopment extends \AGR\Models\ActionCard
{
  public function __construct($row)
  {
    parent::__construct($row);
    $this->id = 'ActionHouseRedevelopment';
    $this->name = clienttranslate('House Redevelopment');
    $this->desc = ['<UPGRADE>', '[▷] 1<MAJOR>/<MINOR>'];
    $this->tooltipDesc = [clienttranslate('[Renovation]'), '<UPGRADE>', clienttranslate('[then]'), '1<MAJOR>/<MINOR>'];
    $this->tooltip = [
      clienttranslate('You may only build a major improvement or play a minor improvement if you renovate first.'),
      clienttranslate('You are not allowed to renovate your house twice in a single action.'),
    ];

    $this->isNotBeginner = true;
    $this->stage = 2;
    $this->flow = [
      'type' => NODE_SEQ,
      'childs' => [
        ['action' => RENOVATION],
        Utils::wrapOptional(
          [
            'action' => IMPROVEMENT,
            'args' => [
              'types' => [MINOR, MAJOR],
              'actionType' => 'MajorOrMinor',
            ],
          ]
        ),
      ],
    ];
  }
}
