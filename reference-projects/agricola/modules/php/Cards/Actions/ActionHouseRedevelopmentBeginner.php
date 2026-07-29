<?php
namespace AGR\Cards\Actions;
use AGR\Helpers\Utils;
class ActionHouseRedevelopmentBeginner extends \AGR\Models\ActionCard
{
  public function __construct($row)
  {
    parent::__construct($row);
    $this->id = 'ActionHouseRedevelopmentBeginner';
    $this->name = clienttranslate('House Redevelopment');
    $this->actionCardType = 'HouseRedevelopment';
    $this->desc = ['<UPGRADE>', '[▷] 1 <MAJOR>'];
    $this->tooltipDesc = [clienttranslate('[Renovation]'), '<UPGRADE>', clienttranslate('[then]'), '1 <MAJOR>'];
    $this->tooltip = [
      clienttranslate('You may only build a major improvement if you renovate first.'),
      clienttranslate('You are not allowed to renovate your house twice in a single action.'),
    ];

    $this->isBeginner = true;
    $this->stage = 2;
    $this->flow = [
      'type' => NODE_SEQ,
      'childs' => [
        ['action' => RENOVATION],
        Utils::wrapOptional([
          'action' => IMPROVEMENT,
          'args' => [
            'types' => [MAJOR],
          ],
        ]),
      ],
    ];
  }
}
