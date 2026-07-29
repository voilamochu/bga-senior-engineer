<?php
namespace AGR\Cards\C;
use AGR\Helpers\Utils;

class C15_Trellis extends \AGR\Models\MinorImprovement
{
  public function __construct($row)
  {
    parent::__construct($row);
    $this->id = 'C15_Trellis';
    $this->name = clienttranslate('Trellis');
    $this->deck = 'C';
    $this->number = 15;
    $this->category = FARM_PLANNER;
    $this->desc = [
      clienttranslate(
        'Each time before you use the __Pig Market__ accumulation space, you can take a __Build Fences__ action. (You must pay <WOOD> for the fences as usual.)'
      ),
    ];
    $this->prerequisite = clienttranslate('2 Occupations');
    $this->occupationPrerequisites = ['min' => 2];    
    $this->isCorbariusOrDulcinaria = true;
  }

  public function isListeningTo($event)
  {
    return $this->isActionCardEvent($event, 'PigMarket');
  }

  public function onPlayerPlaceFarmer($player, $args)
  {
    return [
      'type' => NODE_SEQ,
      'optional' => true,
      'countAsUse' => true,
      'childs' => [
        [
          'action' => FENCING,
          'args' => ['costs' => Utils::formatCost([WOOD => 1])],
        ],
      ],
    ];
  }
}
