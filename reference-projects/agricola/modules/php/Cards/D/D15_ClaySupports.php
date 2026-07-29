<?php
namespace AGR\Cards\D;

class D15_ClaySupports extends \AGR\Models\MinorImprovement
{
  public function __construct($row)
  {
    parent::__construct($row);
    $this->id = 'D15_ClaySupports';
    $this->name = clienttranslate('Clay Supports');
    $this->deck = 'D';
    $this->number = 15;
    $this->category = FARM_PLANNER;
    $this->desc = [
      clienttranslate(
        'Each time you build a clay room, you can pay 2 <CLAY>, 1 <WOOD>, and 1 <REED> instead of 5 <CLAY> and 2 <REED>.'
      ),
    ];
    $this->cost = [
      WOOD => 2,
    ];
    $this->replacesCostFor = ['ComputeCostsConstruct'];
  }

  public function onPlayerComputeCostsConstruct($player, &$args)
  {
    if ($args['type'] == 'roomClay') {
      $args['costs']['trades'][] = [
        CLAY => 2,
        WOOD => 1,
        REED => 1,
        'sources' => [$this->id],
      ];
    }
  }
}
