<?php
namespace AGR\Cards\Actions;
use AGR\Helpers\Utils;

class ActionFencing extends \AGR\Models\ActionCard
{
  public function __construct($row)
  {
    parent::__construct($row);
    $this->id = 'ActionFencing';
    $this->name = clienttranslate('Fencing');
    $this->desc = ['1<WOOD><ARROW><FENCE>'];
    $this->tooltipDesc = [clienttranslate('[Build fences]'), '1<WOOD><ARROW><FENCE>'];
    $this->tooltip = [
      clienttranslate('You can build any number of fences, paying 1 wood for each new fence you build.'),
      clienttranslate(
        'You are allowed to fence a stable or divide an existing pasture in several smaller ones by building fences on the fence spaces inside the pasture.'
      ),
    ];

    $this->stage = 1;
    $this->flow = [
      'action' => FENCING,
      'args' => [
        'costs' => Utils::formatCost([WOOD => 1]),
      ],
    ];
  }
}
