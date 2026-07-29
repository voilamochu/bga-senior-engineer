<?php

namespace AGR\Cards\C;

use AGR\Core\Notifications;
use AGR\Managers\Meeples;
use AGR\Managers\Players;
use AGR\Models\Occupation;

class C130_OutskirtsDirector extends Occupation
{
  public function __construct($row)
  {
    parent::__construct($row);
    $this->id = 'C130_OutskirtsDirector';
    $this->name = clienttranslate('Outskirts Director');
    $this->deck = 'C';
    $this->number = 130;
    $this->category = ACTIONS_BOOSTER;
    $this->desc = [
      clienttranslate(
        'Each time you use the __Grove__ or __Hollow__ accumulation space, you can place 2 <REED> from the general supply on the other space. If you do, you can immediately place another person.'
      ),
    ];
    $this->players = '3+';
    $this->isCorbariusOrDulcinaria = true;

    $this->rulings = [
      clienttranslate('You may use the ability multiple times in a row.'),
    ];
  }

  public function isListeningTo($event)
  {
    if (!$this->isActionEvent($event, 'PlaceFarmer')) {
      return false;
    }

    return $event['actionCardType'] == 'Grove' ||
      $event['actionCardType'] == 'Hollow';
  }

  public function onPlayerAfterPlaceFarmer($player, $event)
  {
    $other = $event['actionCardType'] == 'Grove' ? 'ActionHollow' : 'ActionGrove';
    if (Players::count() >= 4 && $other == 'ActionHollow') {
      $other = 'ActionHollow4';
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
            'method' => 'placeReed',
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

  public function getPlaceReedDescription($actionCardId)
  {
    return [
      'log' => clienttranslate('Add 2${resources_desc} on ${action_space}'),
      'args' => [
        'i18n' => ['action_space'],
        'resources_desc' => '<REED>',
        'action_space' => $actionCardId == 'ActionGrove' ? clienttranslate('Grove') : clienttranslate('Hollow'),
      ],
    ];
  }

  public function placeReed($actionCardId)
  {
    $resourceIds = Meeples::createResourceOnCard(REED, $actionCardId, 2);
    Notifications::accumulate(Meeples::getMany($resourceIds), true);
  }
}
