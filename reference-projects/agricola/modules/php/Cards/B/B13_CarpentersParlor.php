<?php
namespace AGR\Cards\B;
use AGR\Helpers\Utils;

class B13_CarpentersParlor extends \AGR\Models\MinorImprovement
{
  public function __construct($row)
  {
    parent::__construct($row);
    $this->id = 'B13_CarpentersParlor';
    $this->name = clienttranslate("Carpenter's Parlor");
    $this->deck = 'B';
    $this->number = 13;
    $this->category = FARM_PLANNER;
    $this->desc = [clienttranslate('Wooden rooms only cost you 2 <WOOD> and 2 <REED> each.')];
    $this->cost = [
      WOOD => 1,
      STONE => 1,
    ];
    $this->replacesCostFor = ['ComputeCostsConstruct'];
  }

  public function onPlayerComputeCostsConstruct($player, &$args)
  {
    if ($args['type'] == 'roomWood') {
      Utils::addCost($args['costs'], [WOOD => 2, REED => 2], $this->id);
    }
  }
}
