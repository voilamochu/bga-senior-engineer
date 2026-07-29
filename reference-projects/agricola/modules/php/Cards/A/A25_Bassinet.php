<?php
namespace AGR\Cards\A;

use AGR\Core\Globals;
use AGR\Managers\ActionCards;
use AGR\Managers\Farmers;
use AGR\Managers\PlayerCards;

class A25_Bassinet extends \AGR\Models\MinorImprovement
{
  public function __construct($row)
  {
    parent::__construct($row);
    $this->id = 'A25_Bassinet';
    $this->name = clienttranslate('Bassinet');
    $this->deck = 'A';
    $this->number = 25;
    $this->category = ACTIONS_BOOSTER;
    $this->desc = [
      clienttranslate(
        'You can place a(nother) person on the first non-accumulating action space used in each work phase, if there is only 1 person, including newborns, on that space. (There can never be two people on __Meeting Place__.)'
      ),
    ];
    $this->cost = [
      WOOD => '1',
      REED => '1',
    ];
    $this->isArtifexOrBubulcus = true;

    $this->rulings = [
      clienttranslate('This refers to 1 specific space, not 1 space for every player\'s first non-accumulating action space.'),
      clienttranslate('The relevant space can be provided by another card, such as the one from __Forest Tallyman__ (A162).'),
    ];
  }

  public function onBuy($player)
  {
    if (!Globals::isWorkPhase()) {
      return;
    }
    $first = Globals::getBassinetFirstSpace();
    if ($first === 'EXCLUDED') {
      $this->setInfobox(clienttranslate('Meeting Place'));
    } elseif ($first !== '') {
      $this->setInfobox($this->resolveSpaceName($first));
    }
  }

  public function isListeningTo($event)
  {
    return ($this->isPlayerEvent($event) && $event['type'] == 'ReturnHome') ||
      $this->isActionEvent($event, 'PlaceFarmer', null);
  }

  public function onAfterPlaceFarmer($player, $event)
  {
    if (!Globals::isWorkPhase() || $this->getInfobox() !== null) {
      return;
    }
    return [
      'action' => SPECIAL_EFFECT,
      'args' => [
        'cardId' => $this->id,
        'method' => 'syncInfobox',
      ],
    ];
  }

  public function onPlayerReturnHome($player, $event)
  {
    return [
      'action' => SPECIAL_EFFECT,
      'args' => [
        'cardId' => $this->id,
        'method' => 'clearInfobox',
      ],
    ];
  }

  public function isIndependentClearInfobox()
  {
    return true;
  }

  public function clearInfobox()
  {
    if ($this->getInfobox() !== null) {
      $this->updateInfobox(null);
    }
  }

  public function isIndependentSyncInfobox()
  {
    return true;
  }

  public function syncInfobox()
  {
    $first = Globals::getBassinetFirstSpace();
    if ($first === '') {
      return;
    }
    if ($first === 'EXCLUDED') {
      $this->setInfobox(clienttranslate('Meeting Place'));
    } else {
      $this->setInfobox($this->resolveSpaceName($first));
    }
  }

  private function resolveSpaceName($cardId)
  {
    $card = ActionCards::get($cardId, false);
    if (!$card instanceof \AGR\Models\ActionCard) {
      $card = PlayerCards::get($cardId);
    }
    return $card->getName();
  }

  public function onPlayerComputeArgsPlaceFarmer($player)
  {
    if (!Globals::isWorkPhase()) {
      return;
    }

    $first = Globals::getBassinetFirstSpace();

    // Not set yet or blocked
    if ($first === '' || $first === 'EXCLUDED') {
      return;
    }

    // Only the Bassinet owner can use Bassinet
    $owner = $this->getPlayer();
    if ($player->getId() !== $owner->getId()) {
      return;
    }

    // Must currently have exactly 1 person on that space
    if (Farmers::getOnCard($first)->count() != 1) {
      return;
    }

    return [[
      'actionCardId'     => $first,
      'playerConstraint' => 'dummy',
      'ignoreResources'  => false,
    ]];
  }
}
