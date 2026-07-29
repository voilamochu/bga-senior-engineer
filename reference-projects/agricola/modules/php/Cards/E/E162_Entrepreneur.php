<?php
namespace AGR\Cards\E;
use AGR\Managers\Meeples;
use AGR\Core\Notifications;
use AGR\Core\Engine;

class E162_Entrepreneur extends \AGR\Models\Occupation
{
  public function __construct($row)
  {
    parent::__construct($row);
    $this->id = 'E162_Entrepreneur';
    $this->name = clienttranslate('Entrepreneur');
    $this->deck = 'E';
    $this->author = 'inoshishi';
    $this->number = 162;
    $this->category = 'BUILDING_RESOURCES';
    $this->desc = [
      clienttranslate(
        'At the start of each round, you can move 1 <FOOD> to this card or discard 1 <FOOD> from it. If you do either, you get 1 building resource of a type you currently do not have.'
      ),
    ];
    $this->players = '4+';
    $this->holder = true;
  }

  public function isListeningTo($event)
  {
    return $this->isPlayerEvent($event) && 
      $event['type'] == 'StartOfTurn' &&
      $this->getResourceChoices() != [];
  }

  public function getResourceChoices()
  {
    $player = $this->getPlayer();
    $resources = [];

    foreach ([WOOD, CLAY, REED, STONE] as $res) {
      if ($player->countReserveResource($res) == 0) {
        $resources[] = $res;
      }
    }

    return $resources;
  }

  public function onPlayerStartOfTurn($player, $event)
  {
    $options = [
      [
        'type' => NODE_SEQ,
        'childs' => [
          $this->payNode([FOOD => 1]),
          [
            'action' => SPECIAL_EFFECT,
            'args' => [
              'cardId' => $this->id,
              'method' => 'addFood',
            ]
          ]
       ]
      ]
    ];

    if (!Meeples::getResourcesOnCard($this->id, null, FOOD)->empty()) {
      $options[] = [
        'action' => SPECIAL_EFFECT,
        'args' => [
          'cardId' => $this->id,
          'method' => 'discardFood',
        ]
      ];
    }

    return [
      'type' => NODE_SEQ,
      'optional' => true,
      'childs' => [
        [
          'type' => NODE_XOR,
          'childs' => $options,
        ],
        [
          'action' => SPECIAL_EFFECT,
          'args' => [
            'cardId' => $this->id,
            'method' => 'gainResource',        
          ]
        ],
      ]
    ];
  }

  public function addFood()
  {
    $player = $this->getPlayer();

    $created = Meeples::createResourceInLocation(FOOD, $this->id, $player->getId(), null, null, 1);
    Notifications::accumulate(Meeples::getMany($created), true);
  }

  public function getDiscardFoodDescription()
  {
    return [
      'log' => clienttranslate('Discard ${resources_desc} from ${card}'),
      'args' => [
        'resources_desc' => '1 <FOOD>',
        'card' => $this->name,
      ],
    ];
  }

  public function discardFood()
  {
    $meeple = $this->getNextResource(FOOD);
    Meeples::DB()->delete($meeple['id']);
    Notifications::silentKill([$meeple]);
  }

  public function gainResource()
  {
    $player = $this->getPlayer();
    $resources = $this->getResourceChoices();

    $flow = [];
    foreach ($resources as $res) {
      $flow[] = $this->gainNode([$res => 1]);
    }

    Engine::insertAsChild(['type' => NODE_XOR, 'childs' => $flow]);
  }
}
