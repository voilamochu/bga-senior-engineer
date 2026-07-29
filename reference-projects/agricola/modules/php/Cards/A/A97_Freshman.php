<?php
namespace AGR\Cards\A;

use AGR\Actions\Exchange;
use AGR\Helpers\Utils;
use AGR\Managers\ActionCards;
use AGR\Managers\PlayerCards;

class A97_Freshman extends \AGR\Models\Occupation
{
  public function __construct($row)
  {
    parent::__construct($row);
    $this->id = 'A97_Freshman';
    $this->name = clienttranslate('Freshman');
    $this->deck = 'A';
    $this->number = 97;
    $this->category = ACTIONS_BOOSTER;
    $this->desc = [
      clienttranslate(
        'Each time you get a __Bake Bread__ action, instead of taking the action, you can play an occupation without paying an occupation cost (at most once per turn).'
      ),
    ];
    $this->players = '1+';
    $this->isArtifexOrBubulcus = true;
    $this->bannedStrong1or2p = true;
    $this->bannedStrong3or4p = true;

    $this->rulings = [
      clienttranslate('The effect is limited to once per turn, so for example cannot be triggered multiple times in the same turn with __Bread Paddle__ (B025).'),
      clienttranslate('You may use the __Grain Utilization__ space while being unable to __Sow__ or __Bake Bread__, as this card substitutes the __Bake Bread__ action.'),
    ];
  }

  public function isListeningTo($event)
  {
    return $this->isActionEvent($event, 'PlaceFarmer') ||
      $this->isDuringActionEvent($event, 'PlaceFarmer') ||
      (!$this->isFlagged() && $event['method'] == 'computeReplaceExchange' && $this->isPlayerEvent($event) && $this->checkArgs($event['args'] ?? []));
  }

  function checkArgs($args) {
    return Exchange::isBread($args) && !($args['checkedReplaceAction'] ?? false);
  }

  public function onPlayerAfterPlaceFarmer($player, $args)
  {
    return $this->unflagCardNode();
  }

  // Reset onPlaceFarmer too to deal with Baking Course/Sour dough-type uses
  public function onPlayerPlaceFarmer($player, $args)
  {
    return $this->unflagCardNode();
  }
  public function onPlayerComputeArgsPlaceFarmer($player)
  {
    $added = [];
    $cIds = ['ActionGrainUtilization'];
    foreach ($cIds as $cId) {
      if (ActionCards::get($cId) && ActionCards::get($cId)->isVisible()) {
        $added[] = ['actionCardId' => $cId, 'ignoreResources' => true];
      }
    }
    return $added;
  }
  public function onPlayerIsDoable($player, &$args)
  {
    if ($args['isDoable']) {
      return;
    }
    if ($args['action'] == EXCHANGE && !$this->isFlagged()) {
      if (is_object($args['ctx']) && !is_null($args['ctx']->getArgs())) {
        if ($this->checkArgs($args['ctx']->getArgs()) && $this->canPlayOccupation($player)) {
          $args['isDoable'] = true;
        }
      }
    }
  }

  // The substitute must actually be takeable: an empty hand can't replace the bake
  public function canPlayOccupation($player)
  {
    foreach (PlayerCards::getAvailables(OCCUPATION) as $occ) {
      if ($occ->isBuyable($player, true)) {
        return true;
      }
    }
    return false;
  }
  public function onPlayerComputeReplaceExchange($player, $event)
  {
    // Inside a space's committed bake, the substitution IS the action taken — no declining into an empty use
    return [
      'type' => NODE_SEQ,
      'optional' => !Utils::isActionSpaceId($event['cardId'] ?? null),
      'childs' => [
        $this->flagCardNode(),
        [
          'action' => OCCUPATION,
          'cardId' => $this->id,
          'optional' => false,
          'source' => $this->name,
          'args' => ['cost' => []],
        ],
      ],
    ];
  }
}

