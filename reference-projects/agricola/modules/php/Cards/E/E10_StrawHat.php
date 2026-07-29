<?php
namespace AGR\Cards\E;

use AGR\Core\Globals;
use AGR\Core\Engine;
use AGR\Managers\ActionCards;
use AGR\Managers\Farmers;

class E10_StrawHat extends \AGR\Models\MinorImprovement
{
  public function __construct($row)
  {
    parent::__construct($row);
    $this->id = 'E10_StrawHat';
    $this->name = clienttranslate('Straw Hat');
    $this->deck = 'E';
    $this->author = 'kulbrot';
    $this->number = 10;
    $this->category = 'ACTION_-_GUEST';
    $this->desc = [
      clienttranslate(
        'At the end of the work phases of rounds 3 and 6, you can move your person from the __Farmland__ action space to an unoccupied action space and take that action, or get 1 <FOOD>.'
      ),
    ];
    $this->cost = [
      REED => 1,
    ];
  }

  public function isListeningTo($event)
  {
    return $this->isPlayerEvent($event) &&
      $event['type'] == 'EndWorkPhase' &&
      (Globals::getTurn() == 3 || Globals::getTurn() == 6);
  }

  public function onPlayerEndWorkPhase($player, $event)
  {
    $childs = [
      $this->gainNode([FOOD => 1]),
    ];

    if (!Farmers::getOnCard('ActionFarmland', $this->pId)->empty()) {
      $farmer = Farmers::getOnCard('ActionFarmland', $player->getId())->last();
      $childs[] = [
        'action' => SPECIAL_EFFECT,
        'args' => [
          'cardId' => $this->id,
          'method' => 'moveFarmer',
          'args' => [$farmer]
        ]
      ];
    }

    return [
      'type' => NODE_XOR,
      'childs' => $childs
    ];
  }

  public function getMoveFarmerDescription($farmer)
  {
    return clienttranslate('Move your person from Farmland to another action space');
  }

  public function argsMoveFarmer($farmer)
  {
    return [
      'cardId' => $this->id,
      'description' => clienttranslate('${actplayer} may move their person from Farmland to another action space (Straw Hat)'),
      'descriptionmyturn' => clienttranslate('${you} may move your person from Farmland to another action space (Straw Hat)'),
      'spaces' => $this->getSpaces(),
      'farmer' => $farmer,
    ];
  }

  public function actMoveFarmer($space, $farmer)
  {
    $flow = $this->useActionSpaceNode($space, $farmer);
    Engine::insertAsChild($flow);
  }

  public function getSpaces()
  {
    $player = $this->getPlayer();
    $spaces = [];

    $actionSpaces = ActionCards::getVisible();
    foreach ($actionSpaces as $actionSpace) {
      if ($actionSpace->canBePlayed($player)) {
        $spaces[] = $actionSpace->getId();
      }
    }

    return $spaces;
  }
}