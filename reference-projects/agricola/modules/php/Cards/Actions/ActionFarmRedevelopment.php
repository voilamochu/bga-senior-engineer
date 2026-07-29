<?php
namespace AGR\Cards\Actions;
use AGR\Helpers\Utils;

class ActionFarmRedevelopment extends \AGR\Models\ActionCard
{
  public function __construct($row)
  {
    parent::__construct($row);
    $this->id = 'ActionFarmRedevelopment';
    $this->name = clienttranslate('Farm Redevelopment');
    $this->desc = ['<UPGRADE>', '[▷] 1<WOOD><ARROW><FENCE>'];
    $this->tooltipDesc = [
      clienttranslate('[Renovation]'),
      '<UPGRADE>',
      clienttranslate('[then]'),
      clienttranslate('[Build fences]'),
      '1<WOOD><ARROW><FENCE>',
    ];
    $this->tooltip = [
      clienttranslate(
        'You can only build fences if you renovate first. You are not allowed to renovate twice in a single action.'
      ),
    ];

    $this->stage = 6;
    $this->flow = [
      'type' => NODE_SEQ,
      'childs' => [
        ['action' => RENOVATION],
        [
          'type' => NODE_SEQ,
          'optional' => true,
          'forceConfirmation' => true,
          'childs' => [
            [
              'action' => FENCING,
              'args' => [
                'costs' => Utils::formatCost([WOOD => 1]),
              ],
            ],
          ],
        ],
      ],
    ];
  }
}
