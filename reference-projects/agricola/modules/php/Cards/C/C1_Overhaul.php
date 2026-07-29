<?php
namespace AGR\Cards\C;
use AGR\Core\Notifications;
use AGR\Managers\Fences;
use AGR\Managers\Meeples;
use AGR\Managers\Players;
use AGR\Helpers\Utils;

class C1_Overhaul extends \AGR\Models\MinorImprovement
{
  public function __construct($row)
  {
    parent::__construct($row);
    $this->id = 'C1_Overhaul';
    $this->name = clienttranslate('Overhaul');
    $this->deck = 'C';
    $this->number = 1;
    $this->category = FARM_PLANNER;
    $this->desc = [
      clienttranslate(
        'Immediately raze all of your fences, add up to 3 fences from your supply, and rebuild them. (You do not lose any animals during this.)'
      ),
    ];
    $this->passing = true;
    $this->cost = [
      WOOD => 1,
    ];
    $this->prerequisite = clienttranslate('2 Occupations');
    $this->occupationPrerequisites = ['min' => 2];
    $this->isCorbariusOrDulcinaria = true;

    $this->rulings = [
      clienttranslate('This counts as building fences for the purposes of other cards.'),
    ];
  }
  
  public function onBuy($player)
  {
    $n = 0;
    foreach (Fences::getOnBoard($player->getId()) as $fence) {
      if (!Fences::isWoodPalisade($fence)) {
        $n++;
      }
    }
    if ($n == 0) {
      return;
    }
      
    return [
      'type' => NODE_SEQ,
      'childs' => [
        [
          'action' => SPECIAL_EFFECT,
          'args' => [
            'cardId' => $this->id,
            'method' => 'returnFences',
          ],
        ],
        [
          'action' => FENCING,
          'args' => [
            'costs' => Utils::formatCost([WOOD => 0]),
            'min' => $n,
            'max' => $n+3,
            'noWoodPalisades' => true,
          ]
        ]
      ],
    ];
  }
  
  public function returnFences()
  {
    $player = Players::getActive();
    $fences = Fences::getOnBoard($player->getId());

    $returned = [];
    foreach ($fences as &$fence) {
      if (!Fences::isWoodPalisade($fence)) {
        $returned[] = Meeples::returnToReserve($player, $fence);
      }
    }

    if (!empty($returned)) {
      Notifications::returnToReserve($player, $returned);
    }
  }
}
