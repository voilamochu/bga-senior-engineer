<?php
namespace AGR\Cards\E;
use AGR\Managers\Meeples;
use AGR\Core\Notifications;

class E166_Roastmaster extends \AGR\Models\Occupation
{
  public function __construct($row)
  {
    parent::__construct($row);
    $this->id = 'E166_Roastmaster';
    $this->name = clienttranslate('Roastmaster');
    $this->deck = 'E';
    $this->author = 'joeknapp38';
    $this->number = 166;
    $this->category = 'ANIMALS_-_CATTLE';
    $this->desc = [
      clienttranslate(
        'Each time you use the __Traveling Players__ or __Fishing__ accumulation spaces, you can move exactly 1 <FOOD> from that space to the other to get 1 <CATTLE>.'
      ),
    ];
    $this->players = '4+';
  }

  public function isListeningTo($event)
  {
    return $this->isActionCardEvent($event, 'TravelingPlayers') ||
      $this->isActionCardEvent($event, 'Fishing');
  }
  
  public function onPlayerPlaceFarmer($player, $event)
  {
    $placed = $event['actionCardType'] == 'Fishing' ? 'ActionFishing' : 'ActionTravelingPlayers';
    $other = $event['actionCardType'] == 'Fishing' ? 'ActionTravelingPlayers' : 'ActionFishing';

    return [
      'type' => NODE_SEQ,
      'optional' => true,
      'childs' => [
        [
          'action' => SPECIAL_EFFECT,
          'args' => [
            'cardId' => $this->id,
            'method' => 'moveFood',
            'args' => [$placed, $other],
          ],
        ],
        $this->gainNode([CATTLE => 1]),
      ]
    ];
  }

  public function getMoveFoodDescription($placed, $other)
  {
    return [
      'log' => clienttranslate('Move ${resources_desc} from ${action_space1} to ${action_space2}'),
      'args' => [
        'i18n' => ['action_space1', 'action_space2'],
        'resources_desc' => '1 <FOOD>',
        'action_space1' => $placed == 'ActionFishing' ? clienttranslate('Fishing') : clienttranslate('Traveling Players'),
        'action_space2' => $other == 'ActionFishing' ? clienttranslate('Fishing') : clienttranslate('Traveling Players'),
      ],
    ];
  }

  public function moveFood($placed, $other)
  {
    $food = Meeples::getResourcesOnCard($placed, null, FOOD)->first();
    $silentKill = Meeples::deleteResources([$food]);
    Notifications::silentKill($silentKill);

    $newFood = Meeples::createResourceOnCard(FOOD, $other, 1);
    Notifications::accumulate(Meeples::getMany($newFood), true);
  }
}
