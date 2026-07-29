<?php
namespace AGR\Cards\D;
use AGR\Helpers\Utils;

class D149_CasualWorker extends \AGR\Models\Occupation
{
  public function __construct($row)
  {
    parent::__construct($row);
    $this->id = 'D149_CasualWorker';
    $this->name = clienttranslate('Casual Worker');
    $this->deck = 'D';
    $this->number = 149;
    $this->category = FARM_PLANNER;
    $this->desc = [
      clienttranslate(
        'Each time another player uses a __Quarry__ accumulation space, you can choose to get 1 <FOOD> or build a stable without paying wood.'
      ),
    ];
    $this->players = '4+';
    $this->isCorbariusOrDulcinaria = true;
  }
  
  public function isListeningTo($event)
  {
    return $this->isActionCardEvent($event, 'EasternQuarry', 'opponent') ||
      $this->isActionCardEvent($event, 'WesternQuarry', 'opponent');
  }
  
  public function onOpponentPlaceFarmer($player, $event)
  {
    return [
      'type' => NODE_XOR,
      'pId' => $this->pId,
      'childs' => [
        $this->gainNode([FOOD => 1]),
        [
          'action' => STABLES,
          'args' => [
            'max' => 1,
            'costs' => Utils::formatCost(['max' => 1]),
          ],
        ]
      ]
    ];
  }
}
