<?php
namespace AGR\Cards\D;

use AGR\Core\Engine;
use AGR\Managers\ActionCards;
use AGR\Managers\Farmers;
use AGR\Managers\Players;

class D51_Archway extends \AGR\Models\PlayerActionCard
{
  protected $type = MINOR;
  public function __construct($row)
  {
    parent::__construct($row);
    $this->id = 'D51_Archway';
    $this->name = clienttranslate('Archway');
    $this->deck = 'D';
    $this->number = 51;
    $this->category = FOOD_PROVIDER;
    $this->desc = [
      clienttranslate(
        'This card is an action space for all. A player who uses it immediately gets 1 <FOOD>. Immediately before the returning home phase, they can use an unoccupied action space with the person from this card.'
      ),
    ];
    $this->vp = 4;
    $this->cost = [
      CLAY => 2,
    ];
    $this->prerequisite = ('No Occupations');
    $this->occupationPrerequisites = ['max' => 0];
    $this->isCorbariusOrDulcinaria = true;
    $this->flow = [
      'action' => SPECIAL_EFFECT,
      'args' => [
        'method' => 'activate',
        'cardId' => $this->id,
        'args' => [],
      ],
    ];

    $this->rulings = [
      clienttranslate('You cannot move a farmer from Archway to an occupied space, even if another card would otherwise allow you to treat that space as unoccupied. Exception: if you have played __Forest School__ (A028), you can move to an occupied __Lessons__ action space.'),
    ];
  }

  public function activate()
  {
    $player = Players::getActive();
    Engine::insertAsChild($this->gainNode([FOOD => 1], $player->getId()));
  }

  public function isListeningTo($event)
  {
    return $this->isPlayerEvent($event, null) && $event['type'] == 'BeforeReturnHome';
  }

  public function onBeforeReturnHome($player, $event)
  {
    $farmers = Farmers::getOnCard('D51_Archway');
    $flow = [
      'type' => NODE_SEQ,
      'childs' => []
    ];

    foreach ($farmers as $farmer) {
      $pId = $farmer['pId'];
      if ($pId == $player->getId()) {
        $flow['childs'][] = [
          'action' => SPECIAL_EFFECT,
          'optional' => true,
          'pId' => $pId,
          'args' => [
            'cardId' => $this->id,
            'method' => 'moveFarmer',
            'args' => [$farmer]
          ]
        ];
      }
    }

    return $flow;
  }

  public function onPlayerBeforeReturnHome($player, $event)
  {
    if (Farmers::getOnCard('D51_Archway')->empty()) {
      return;
    }

    return $this->onBeforeReturnHome($player, $event);
  }

  public function onOpponentBeforeReturnHome($player, $event)
  {
    if (Farmers::getOnCard('D51_Archway')->empty()) {
      return;
    }

    return $this->onBeforeReturnHome($player, $event);
  }

  public function getMoveFarmerDescription($farmer)
  {
    return clienttranslate('Move your person from Archway to another action space');
  }

  public function argsMoveFarmer($farmer)
  {
    return [
      'cardId' => $this->id,
      'description' => clienttranslate('${actplayer} may move their person from Archway to another action space'),
      'descriptionmyturn' => clienttranslate('${you} may move your person from Archway to another action space'),
      'spaces' => $this->getSpaces($farmer),
      'farmer' => $farmer,
    ];
  }

  public function actMoveFarmer($space, $farmer)
  {
    $flow = $this->useActionSpaceNode($space, $farmer);
    Engine::insertAsChild($flow);
  }

  public function getSpaces($farmer)
  {
    $player = Players::get($farmer['pId']);
    $spaces = [];

    $actionSpaces = ActionCards::getVisible();
    foreach ($actionSpaces as $actionSpace) {
      if ($actionSpace->canBePlayed($player) && $actionSpace->id != 'D51_Archway') {
        $spaces[] = $actionSpace->getId();
      }
    }

    if ($player->hasPlayedCard('A28_ForestSchool')) {
      foreach ($actionSpaces as $actionSpace) {
        $cId = $actionSpace->getId();
        if ($actionSpace->getActionCardType() == 'Lessons'
          && !in_array($cId, $spaces)
          && $actionSpace->canBePlayed($player, 'dummy')) {
          $spaces[] = $cId;
        }
      }
    }

    return $spaces;
  }
}
