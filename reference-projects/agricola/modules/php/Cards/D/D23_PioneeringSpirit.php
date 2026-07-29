<?php
namespace AGR\Cards\D;

use AGR\Core\Engine;
use AGR\Core\Globals;

class D23_PioneeringSpirit extends \AGR\Models\PlayerActionCard
{
  protected $type = MINOR;
  public function __construct($row)
  {
    parent::__construct($row);
    $this->id = 'D23_PioneeringSpirit';
    $this->name = clienttranslate('Pioneering Spirit');
    $this->deck = 'D';
    $this->number = 23;
    $this->category = ACTIONS_BOOSTER;
    $this->desc = [
      clienttranslate(
        'This card is an action space for you only. In rounds 3-5, it provides a __Renovation__ action. In rounds 6-8, it provides your choice of 1 <VEGETABLE>, <PIG>, or <CATTLE>.'
      ),
    ];
    $this->privateSpace = true;
    $this->flow = [
      'action' => SPECIAL_EFFECT,
      'args' => [
        'method' => 'activate',
        'cardId' => $this->id,
        'args' => [],
      ],
    ];
    $this->isCorbariusOrDulcinaria = true;
  }

  public function canBePlayed($player, $onlyCheckSpecificPlayer = null, $ignoreResources = false, $ignoreDoable = false)
  {
    return Globals::getTurn() >= 3 && Globals::getTurn() <= 8 &&
      parent::canBePlayed($player, $onlyCheckSpecificPlayer, $ignoreResources);
  }

  public function activate()
  {
    $turn = Globals::getTurn();

    if ($turn >= 3 && $turn <= 5) {
      $flow = [
        'action' => RENOVATION,
        'pId' => $this->pId,
      ];
    } elseif ($turn >= 6 && $turn <= 8) {
      $flow = [
        'type' => NODE_XOR,
        'childs' => [
            $this->gainNode([VEGETABLE => 1]),
            $this->gainNode([PIG => 1]),
            $this->gainNode([CATTLE => 1]),
          ]
      ];
    }

    Engine::insertAsChild($flow);
  }
}