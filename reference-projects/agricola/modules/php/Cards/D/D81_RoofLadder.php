<?php
namespace AGR\Cards\D;
use AGR\Helpers\Utils;

class D81_RoofLadder extends \AGR\Models\MinorImprovement
{
  public function __construct($row)
  {
    parent::__construct($row);
    $this->id = 'D81_RoofLadder';
    $this->name = clienttranslate('Roof Ladder');
    $this->deck = 'D';
    $this->number = 81;
    $this->category = BUILDING_RESOURCE_PROVIDER;
    $this->desc = [
      clienttranslate('Each time you renovate, you pay 1 fewer <REED> and, at the end of the action, you get 1 <STONE>.'),
    ];
    $this->cost = [
      WOOD => 1,
    ];
    $this->isCorbariusOrDulcinaria = true;
  }

  public function isListeningTo($event)
  {
    return $this->isActionEvent($event, 'Renovation');
  }

  public function onPlayerAfterRenovation($player, $event)
  {
    return $this->gainNode([STONE => 1]);
  }

  public function orderComputeCostsRenovation()
  {
    return [
      ['<', 'B145_BrushwoodCollector'],
      ['>', 'D88_Millwright'],
    ];
  }

  public function onPlayerComputeCostsRenovation($player, &$args)
  {
    Utils::addBonus($args['costs'], [REED => -1], $this->id);
  }
}
