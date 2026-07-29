<?php
namespace AGR\Cards\C;

use AGR\Helpers\Utils;

class C16_FieldFences extends \AGR\Models\MinorImprovement
{
  public function __construct($row)
  {
    parent::__construct($row);
    $this->id = 'C16_FieldFences';
    $this->name = clienttranslate('Field Fences');
    $this->deck = 'C';
    $this->number = 16;
    $this->category = FARM_PLANNER;
    $this->desc = [
      clienttranslate(
        'You can immediately take a __Build Fences__ action, during which you do not have to pay <WOOD> for fences that you build next to field tiles.'
      ),
    ];
    $this->cost = [
      FOOD => 2,
    ];
  }

  public function onBuy($player)
  {
    $this->setExtraDatas('inprogress', true);
    return [
      'type' => NODE_SEQ,
      'optional' => true,
      'childs' => [
        [
          'action' => \FENCING,
          'args' => ['fieldFences' => true, 'costs' => Utils::formatCost([WOOD => 1])],
        ],
      ],
    ];
  }
}
