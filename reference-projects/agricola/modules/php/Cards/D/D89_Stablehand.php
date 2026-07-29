<?php
namespace AGR\Cards\D;

use AGR\Helpers\Utils;

class D89_Stablehand extends \AGR\Models\Occupation
{
  public function __construct($row)
  {
    parent::__construct($row);
    $this->id = 'D89_Stablehand';
    $this->name = clienttranslate('Stablehand');
    $this->deck = 'D';
    $this->number = 89;
    $this->isCorbariusOrDulcinaria = true;
    $this->category = FARM_PLANNER;
    $this->desc = [
      clienttranslate(
        'Each time you build at least 1 fence, you can also build a stable without paying <WOOD> for the stable.'
      ),
    ];
    $this->players = '1+';
  }

  public function isListeningTo($event)
  {
    return $this->isActionEvent($event, 'Fencing') && count($event['fences']) >= 1;
  }

  public function onPlayerAfterFencing($player, $event)
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
