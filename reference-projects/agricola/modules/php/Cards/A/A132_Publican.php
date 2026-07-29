<?php
namespace AGR\Cards\A;

use AGR\Helpers\UsedText;
use AGR\Actions\Sow;
use AGR\Core\Engine;
use AGR\Managers\Players;

class A132_Publican extends \AGR\Models\Occupation
{
  public function __construct($row)
  {
    parent::__construct($row);
    $this->id = 'A132_Publican';
    $this->name = clienttranslate('Publican');
    $this->deck = 'A';
    $this->number = 132;
    $this->category = POINTS_PROVIDER;
    $this->desc = [
      clienttranslate(
        'Each time before another player takes an unconditional __Sow__ action, you can give them 1 <GRAIN> from your supply to get 1 bonus <SCORE>.'
      ),
    ];
    $this->players = '3+';
    $this->isArtifexOrBubulcus = true;
    $this->extraVp = true;
    $this->rulings = [
      clienttranslate('The __Publican__ offer will not be made if the only legal way for the sowing player to sow is for the __Publican__ owner to give them <GRAIN>. Otherwise the sowing player is stuck with an impossible __Sow__ action if the __Publican__ owner declines the offer.'),
    ];
    $this->usedText = UsedText::get('GRAIN_GIVEN');
  }

  public function isListeningTo($event)
  {
    return $this->isBeforeEvent($event, 'Sow', 'opponent')
      && ($event['unconditional'] ?? Sow::isUnconditional($event['args'] ?? []));
  }

  public function onOpponentBeforeSow($player, $event)
  {
    // The central problem is that if the Publican decides not to give grain, we have to make sure the sowing player is not stuck with an impossible sow action.
    // Therefore: defer Publican offer until other pre-sow stuff (Seed Pellets etc), and only then make the offer if the player has something else to sow.
    return [
      'action' => SPECIAL_EFFECT,
      'args' => [
        'cardId' => $this->id,
        'method' => 'wrapSowWithDeferredCheck',
        'args' => [$player->getId()],
      ],
    ];
  }

  public function wrapSowWithDeferredCheck($targetPlayerId)
  {
    $current = Engine::getNextUnresolved();

    $node = $current;
    while (!is_null($node)) {
      if ($node->getType() == NODE_PARALLEL && $node->getEventMethod() == 'beforeSow') {
        break;
      }
      $node = $node->getParent();
    }
    if (is_null($node)) {
      return;
    }

    $actionNode = $node->getNextSibling();
    if (is_null($actionNode)) {
      return;
    }

    $deferredCheck = [
      'action' => SPECIAL_EFFECT,
      'args' => [
        'cardId' => $this->id,
        'method' => 'offerIfSowFeasible',
        'args' => [$targetPlayerId],
      ],
    ];

    $actionNodeArray = $actionNode->toArray();
    $actionNodeArray['pId'] = $targetPlayerId;

    $actionNode->replace(
      Engine::buildTree([
        'type' => NODE_SEQ,
        'childs' => [
          $deferredCheck,
          $actionNodeArray,
        ],
      ])
    );
    Engine::save();
  }

  public function offerIfSowFeasible($targetPlayerId)
  {
    $current = Engine::getNextUnresolved();
    $actionNode = is_null($current) ? null : $current->getNextSibling();
    if (is_null($actionNode)) {
      return;
    }

    $target = Players::get($targetPlayerId);
    $reserve = $target->getAllReserveResources();
    $hasSeeds = ($reserve[GRAIN] ?? 0) > 0 || ($reserve[VEGETABLE] ?? 0) > 0
      || ($reserve[WOOD] ?? 0) > 0 || ($reserve[STONE] ?? 0) > 0;
    if (!$hasSeeds || !$target->board()->canSow($reserve)) {
      return;
    }

    Engine::insertAsChild($this->getDeferredOfferFlow($targetPlayerId));
  }

  private function getDeferredOfferFlow($targetPlayerId)
  {
    return [
      'type' => NODE_SEQ,
      'optional' => true,
      'forceConfirmation' => true,
      'pId' => $this->pId,
      'childs' => [
        $this->payNode([GRAIN => 1], null, 1, $targetPlayerId),
        $this->gainNode([SCORE => 1]),
        [
          'action' => SPECIAL_EFFECT,
          'args' => [
            'cardId' => $this->id,
            'method' => 'bumpUsed',
          ],
        ],
      ],
    ];
  }

  public function bumpUsed()
  {
    $this->incStats('used');
  }

  public function isIndependentBumpUsed()
  {
    return true;
  }
}
