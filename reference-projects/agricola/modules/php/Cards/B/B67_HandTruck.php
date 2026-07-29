<?php

namespace AGR\Cards\B;

use AGR\Core\Engine;
use AGR\Helpers\Utils;
use AGR\Models\MinorImprovement;

class B67_HandTruck extends MinorImprovement
{
  public function __construct($row)
  {
    parent::__construct($row);
    $this->id = 'B67_HandTruck';
    $this->name = clienttranslate('Hand Truck');
    $this->deck = 'B';
    $this->number = 67;
    $this->category = CROP_PROVIDER;
    $this->desc = [
      clienttranslate(
        'Each time before you take a __Bake Bread__ action, you also get 1 <GRAIN> for each of your people occupying an accumulation space.'
      ),
    ];
    $this->cost = [
      WOOD => 1,
    ];
    $this->isArtifexOrBubulcus = true;

    $this->rulings = [
      clienttranslate('You must bake if you receive the <GRAIN>.'),
    ];
  }

  public function onPlayerIsDoable($player, &$args)
  {
    if ($args['isDoable'] && $args['action'] != EXCHANGE) {
      return;
    }

    $ctxArgs = $args['ctx'] == null ? null : (is_array($args['ctx']) ? $args['ctx'] : $args['ctx']->getArgs());
    if (($ctxArgs['trigger'] ?? ANYTIME) == BREAD && $player->canBake() && $player->getFarmersOnAccumulationSpaces() > 0) {
      $args['isDoable'] = true;
    }
  }

  public function isListeningTo($event)
  {
    return $this->isBeforeEvent($event, 'Exchange') && ($event['args']['trigger'] ?? ANYTIME) == BREAD
      && $this->getPlayer()->getFarmersOnAccumulationSpaces() > 0;
  }

  public function onPlayerBeforeExchange($player, $event)
  {
    $n = $player->getFarmersOnAccumulationSpaces();

    if ($n == 0) {
      return;
    }

    // A space's bake enabled only by this grain must actually happen
    $forced = Utils::isActionSpaceId($event['cardId'] ?? null) &&
      ($player->getExchangeResources()[GRAIN] ?? 0) == 0 && $player->canBake();

    return [
      'type' => NODE_SEQ,
      'pId' => $this->pId,
      'optional' => !$forced,
      'countAsUse' => true,
      'childs' => [
        $this->gainNode([GRAIN => $n]),
        [
          'action' => SPECIAL_EFFECT,
          'args' => [
            'cardId' => $this->id,
            'method' => 'makeBakeMandatory',
          ],
        ],
      ],
    ];
  }

  public function getMakeBakeMandatoryDescription()
  {
    return clienttranslate("Bake bread");
  }

  public function makeBakeMandatory()
  {
    $parNode = Engine::$tree->getNextUnresolved()->getParent()->getParent();
    $parNode->getNextSibling()->enforceMandatory();
    Engine::save();
  }

}
