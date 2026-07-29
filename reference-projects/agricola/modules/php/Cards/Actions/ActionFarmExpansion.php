<?php
namespace AGR\Cards\Actions;
use AGR\Helpers\Utils;

class ActionFarmExpansion extends \AGR\Models\ActionCard
{
  public function __construct($row)
  {
    parent::__construct($row);
    $this->id = 'ActionFarmExpansion';
    $this->name = clienttranslate('Farm Expansion');
    $this->desc = [
      '5<WOOD>2<REED><ARROW><ROOM_WOOD>',
      '5<CLAY>2<REED><ARROW><ROOM_CLAY>',
      '5<STONE>2<REED><ARROW><ROOM_STONE>',
      '+',
      '2<WOOD> <ARROW> <BARN>',
    ];
    $this->tooltipDesc = [
      clienttranslate('[Build rooms]'),
      '5<WOOD>2<REED><ARROW><ROOM_WOOD>',
      '5<CLAY>2<REED><ARROW><ROOM_CLAY>',
      '5<STONE>2<REED><ARROW><ROOM_STONE>',
      clienttranslate('[and/or]'),
      '2<WOOD> <ARROW> <BARN>',
    ];
    $this->tooltip = [
      clienttranslate(
        'You can build as many rooms and/or stables as you want. You are allowed to only build rooms or only build stables.'
      ),
      clienttranslate(
        'Each new room must be orthogonally adjacent to an existing one. (There is no such restriction for stables.)'
      ),
      clienttranslate('You can only build one stable per farmyard space.'),
    ];

    $this->flow = [
      'type' => NODE_OR,
      'childs' => [
        ['action' => CONSTRUCT],
        [
          'action' => STABLES,
          'args' => [
            'costs' => Utils::formatCost([WOOD => 2]),
          ],
        ],
      ],
    ];
    $this->accumulate = 'left'; // Useful for E148_Lazybones
    $this->skipGains = true;
  }
}