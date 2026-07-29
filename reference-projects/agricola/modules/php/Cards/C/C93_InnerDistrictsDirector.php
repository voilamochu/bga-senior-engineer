<?php

namespace AGR\Cards\C;

use AGR\Core\Notifications;
use AGR\Managers\Meeples;
use AGR\Managers\Players;
use AGR\Models\Occupation;

class C93_InnerDistrictsDirector extends Occupation
{
  public function __construct($row)
  {
    parent::__construct($row);
    $this->id = 'C93_InnerDistrictsDirector';
    $this->name = clienttranslate('Inner Districts Director');
    $this->deck = 'C';
    $this->number = 93;
    $this->category = ACTIONS_BOOSTER;
    $this->desc = [
      clienttranslate(
        'Each time you use the __Forest__ or __Clay Pit__ accumulation space, you can place 1 <STONE> from the general supply on the other space. If you do, you can immediately place another person.'
      ),
    ];
    $this->players = '1+';
    $this->isCorbariusOrDulcinaria = true;

    $this->rulings = [
      clienttranslate('You may use this ability twice on the same turn.'),
    ];
  }

  public function isListeningTo($event)
  {
    if (!$this->isActionEvent($event, 'PlaceFarmer')) {
      return false;
    }

    return $event['actionCardType'] == 'Forest' ||
      $event['actionCardType'] == 'ClayPit';
  }

  public function onPlayerAfterPlaceFarmer($player, $event)
  {
    $other = $event['actionCardType'] == 'Forest' ? 'ActionClayPit' : 'ActionForest';
    if (Players::count() == 1 && $other == 'ActionForest') {
      $other = 'ActionForestSolo';
    }

    return [
      'countAsUse' => true,
      'type' => NODE_SEQ,
      'optional' => true,
      'childs' => [
        [
          'action' => SPECIAL_EFFECT,
          'args' => [
            'cardId' => $this->id,
            'method' => 'placeStone',
            'args' => [$other],
          ],
        ],
        [
          'action' => PLACE_FARMER,
          'optional' => true,
          'args' => [],
          'source' => $this->name,
        ],
      ]
    ];
  }

  public function getPlaceStoneDescription($actionCardId)
  {
    return [
      'log' => clienttranslate('Add ${resources_desc} on ${action_space}'),
      'args' => [
        'i18n' => ['action_space'],
        'resources_desc' => '<STONE>',
        'action_space' => $actionCardId == 'ActionClayPit' ? clienttranslate('Clay Pit') : clienttranslate('Forest'),
      ],
    ];
  }

  public function placeStone($actionCardId)
  {
    $resourceIds = Meeples::createResourceOnCard(STONE, $actionCardId, 1);
    Notifications::accumulate(Meeples::getMany($resourceIds), true);
  }
}
