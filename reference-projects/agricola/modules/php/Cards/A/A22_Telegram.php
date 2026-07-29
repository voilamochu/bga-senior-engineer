<?php
namespace AGR\Cards\A;
use AGR\Managers\Fences;
use AGR\Core\Notifications;
use AGR\Core\Globals;
use AGR\Core\Engine;

class A22_Telegram extends \AGR\Models\MinorImprovement
{
  public function __construct($row)
  {
    parent::__construct($row);
    $this->id = 'A22_Telegram';
    $this->name = clienttranslate('Telegram');
    $this->deck = 'A';
    $this->number = 22;
    $this->category = ACTIONS_BOOSTER;
    $this->desc = [
      clienttranslate(
        'Add 1 to the current round for each fence in your supply and mark the corresponding round space. In that round only, you can place a person from your supply.'
      ),
    ];
    $this->vp = 1;
    $this->cost = [
      FOOD => 2,
    ];
    $this->prerequisite = clienttranslate('At Least 1 Fence in Supply');
    $this->isArtifexOrBubulcus = true;
  }

  public function isBuyable($player, $ignoreResources = false, $args = [])
  {
    if (Fences::countAvailable($player->getId()) == 0) {
      return false;
    }

    return parent::isBuyable($player, $ignoreResources, $args);
  }

  public function onBuy($player)
  {
    $fences = Fences::countAvailable($player->getId());
    $turn = Globals::getTurn() + $fences;

    if ($turn <= 14) {
      $this->setExtraDatas('triggerRound', $turn);
      $this->setInfobox(clienttranslate('Round ') . $turn);
    }
  }

  public function isListeningTo($event)
  {
    return $this->isPlayerEvent($event) &&
      $event['type'] == 'StartOfTurn' &&
      Globals::getTurn() == $this->getExtraDatas('triggerRound');
  }

  public function onPlayerStartOfTurn($player, $event)
  {
    return [
      'action' => SPECIAL_EFFECT,
      'args' => [
        'cardId' => $this->id,
        'method' => 'activate',
      ],
    ];
  }

  public function isIndependentActivate($player)
  {
    return true;
  }

  public function getActivateDescription()
  {
    return clienttranslate('Confirm Telegram');
  }

  public function activate()
  {
    $player = $this->getPlayer();
    if (!$player->hasFarmerInReserve()) {
      return;
    }
    Notifications::message(clienttranslate('${player_name} can place an extra person from their supply this round'), [
      'player_name' => $player->getName(),
    ]);
    $flow = $this->flagCardNode();
    Engine::insertAsChild($flow);
  }
}
