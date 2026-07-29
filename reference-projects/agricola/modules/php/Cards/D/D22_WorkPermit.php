<?php
namespace AGR\Cards\D;
use AGR\Managers\Meeples;
use AGR\Core\Notifications;
use AGR\Core\Globals;
use AGR\Core\Engine;

class D22_WorkPermit extends \AGR\Models\MinorImprovement
{
  public function __construct($row)
  {
    parent::__construct($row);
    $this->id = 'D22_WorkPermit';
    $this->name = clienttranslate('Work Permit');
    $this->deck = 'D';
    $this->number = 22;
    $this->category = ACTIONS_BOOSTER;
    $this->desc = [
      clienttranslate(
        'Add 1 to the current round for each building resource you have and place 1 person from your supply on the corresponding round space. In that round, you can use the person.'
      ),
    ];
    $this->cost = [
      FOOD => 1,
    ];
    $this->prerequisite = clienttranslate('At Least 1 Building Resource');
    $this->isCorbariusOrDulcinaria = true;
  }

  public function isBuyable($player, $ignoreResources = false, $args = [])
  {
    $resNum = $player->countReserveResource(WOOD) +
               $player->countReserveResource(STONE) +
               $player->countReserveResource(CLAY) +
               $player->countReserveResource(REED);

    if ($resNum == 0 || !$player->hasFarmerInReserve()) {
      return false;
    }

    return parent::isBuyable($player, $ignoreResources, $args);
  }

  public function onBuy($player)
  {
    $resNum = $player->countReserveResource(WOOD) +
               $player->countReserveResource(STONE) +
               $player->countReserveResource(CLAY) +
               $player->countReserveResource(REED);

    $turn = Globals::getTurn() + $resNum;

    if ($turn <= 14) {
      return [
        'action' => SPECIAL_EFFECT,
        'args' => [
          'cardId' => $this->id,
          'method' => 'placeFutureFarmer',
          'args' => [$turn],
        ],        
      ];
    }
  }

  public function placeFutureFarmer($turn)
  {
    $player = $this->getPlayer();
    $farmer = $player->getNextFarmerInReserve();

    Meeples::moveToCoords($farmer['id'], 'turn_' . $turn);
    $meeples = Meeples::getMany([$farmer['id']]);
    Notifications::placeMeeplesForFuture($player, [FARMER => 1], [$turn], $meeples);
    $this->setExtraDatas('turn', $turn);
  }

  public function isIndependentActivate($player)
  {
    return true;
  }

  public function getActivateDescription()
  {
    return clienttranslate('Confirm Work Permit');
  }

  public function activate()
  {
    $flow = $this->flagCardNode();
    Engine::insertAsChild($flow);
  }
}
