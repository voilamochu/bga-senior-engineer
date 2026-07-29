<?php
namespace AGR\Cards\B;

use AGR\Helpers\Utils;

class B16_MiningHammer extends \AGR\Models\MinorImprovement
{
  public function __construct($row)
  {
    parent::__construct($row);
    $this->id = 'B16_MiningHammer';
    $this->name = clienttranslate('Mining Hammer');
    $this->deck = 'B';
    $this->number = 16;
    $this->category = FARM_PLANNER;
    $this->desc = [
      clienttranslate(
        'When you play this card, you immediately get 1 <FOOD>. Each time you renovate, you can also build a stable without paying <WOOD>.'
      ),
    ];
    $this->cost = [
      WOOD => 1,
    ];
  }

  public function onBuy($player)
  {
    return $this->gainNode([FOOD => 1]);
  }

  public function isListeningTo($event)
  {
    return $this->isActionEvent($event, 'Renovation');
  }

  public function onPlayerAfterRenovation($player, $event)
  {
    return [
      'action' => STABLES,
      'cardId' => $this->id,
      'optional' => true,
      'args' => [
        'max' => 1,
        'costs' => Utils::formatCost(['max' => 1]),
      ],
    ];
  }
}
