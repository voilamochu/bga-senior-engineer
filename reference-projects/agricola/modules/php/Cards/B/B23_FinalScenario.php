<?php
namespace AGR\Cards\B;
use AGR\Managers\ActionCards;
use AGR\Core\Notifications;
use AGR\Core\Globals;
use AGR\Helpers\CardRulings;

class B23_FinalScenario extends \AGR\Models\MinorImprovement
{
  public function __construct($row)
  {
    parent::__construct($row);
    $this->id = 'B23_FinalScenario';
    $this->name = clienttranslate('Final Scenario');
    $this->deck = 'B';
    $this->number = 23;
    $this->category = ACTIONS_BOOSTER;
    $this->desc = [
      clienttranslate(
        'Reveal the action space card for round 14. Only you can use it until round 14 starts.'
      ),
    ];
    $this->prerequisite = clienttranslate('Round 13 or Before');
    $this->isArtifexOrBubulcus = true;

    $this->rulings = CardRulings::fromKeys([
      'UPDATED_DIGITAL',
    ]);
  }

  public function isBuyable($player, $ignoreResources = false, $args = [])
  {
    $turn = Globals::getTurn();
    if ($turn == 14) {
      return false;
    }

    return parent::isBuyable($player, $ignoreResources, $args);
  }

  public function onBuy($player)
  {
    return [
      'action' => SPECIAL_EFFECT,
      'args' => [
        'cardId' => $this->id,
        'method' => 'revealRound14',
      ],
    ];
  }

  public function isListeningTo($event)
  {
    return $this->isPlayerEvent($event) && 
      $event['type'] == 'AfterRevealAction';
  }

  public function onPlayerAfterRevealAction($player, $event)
  {
    if (Globals::getTurn() == 14) {
      $location = ['turn', 14];

      $card = ActionCards::getInLocation($location)->first();
      $card->setExtraDatas('exclusiveUse', []);
    }
  }

  public function revealRound14()
  {
    $location = ['turn', 14];

    $card = ActionCards::getInLocation($location)->first();
    Notifications::revealActionCard($card);
    ActionCards::moveAllInLocation($location, $location, HIDDEN, VISIBLE);
    $card->setExtraDatas('exclusiveUse', $this->getPlayer()->getId());
  }
}
