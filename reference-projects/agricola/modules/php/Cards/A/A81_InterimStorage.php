<?php
namespace AGR\Cards\A;
use AGR\Managers\Meeples;
use AGR\Core\Engine;
use AGR\Core\Notifications;
use AGR\Core\Stats;
use AGR\Core\Globals;

class A81_InterimStorage extends \AGR\Models\MinorImprovement
{
  public function __construct($row)
  {
    parent::__construct($row);
    $this->id = 'A81_InterimStorage';
    $this->name = clienttranslate('Interim Storage');
    $this->deck = 'A';
    $this->number = 81;
    $this->category = BUILDING_RESOURCE_PROVIDER;
    $this->desc = [
      clienttranslate(
        'Each time you use a clay/reed/stone accumulation space, place 1 <WOOD>/<CLAY>/<REED> on this card. At the start of rounds 7, 11, and 14, move all the goods on this card to your supply.'
      ),
    ];
    $this->cost = [
      FOOD => 2,
    ];
    $this->holder = true;
    $this->isArtifexOrBubulcus = true;
  }

  public function isListeningTo($event)
  {
    return $this->isBeforeCollectEvent($event, CLAY) ||
      $this->isBeforeCollectEvent($event, REED) ||
      $this->isBeforeCollectEvent($event, STONE) ||
      ($this->isPlayerEvent($event) && $event['type'] == 'StartOfTurn');
  }

  public function onPlayerPlaceFarmer($player, $event)
  {
    $map = [CLAY => WOOD, REED => CLAY, STONE => REED];
    
    foreach ([CLAY, REED, STONE] as $type) {
      if ($this->isBeforeCollectEvent($event, $type)) {
        $resource = $map[$type];
        break;
      }
    }

    return [
      'action' => SPECIAL_EFFECT,
      'args' => [
        'cardId' => $this->id,
        'method' => 'placeResource',
        'args' => [$resource],
      ],
    ];
  }

  public function onPlayerStartOfTurn($player, $event)
  {
    $turn = Globals::getTurn();
    if (Meeples::getResourcesOnCard($this->id)->empty()) {
      return;
    }

    if ($turn == 7 || $turn == 11 || $turn == 14) {
      return [
        'action' => SPECIAL_EFFECT,
        'args' => [
          'cardId' => $this->id,
          'method' => 'gainResources',
        ]
      ];
    }
  }

  public function getPlaceResourceDescription($type)
  {
    return [
      'log' => clienttranslate('Place ${resources_desc} on ${card}'),
      'args' => [
        'resources_desc' => '1 <' . strtoupper($type) . '>',
        'card' => $this->name,
      ],
    ];
  }

  public function placeResource($type) 
  {
    $player = $this->getPlayer();  
    $created = Meeples::createResourceInLocation($type, $this->id, $player->getId(), null, null);

    Notifications::accumulate(Meeples::getMany($created), true);
  }
  
  public function getGainResourcesDescription()
  {
    return [
      'log' => clienttranslate('Gain all resources on ${card}'),
      'args' => [
        'card' => $this->name,
      ],
    ];    
  }

  public function gainResources()
  {
    $player = $this->getPlayer();
    $meeples = Meeples::getResourcesOnCard($this->id);

    // Go through RECEIVE so listeners fire (Wolf, bug 232445)
    $meepleIds = [];
    foreach ($meeples as $meeple) {
      $meepleIds[] = $meeple['id'];
      $statName = 'incCards' . ucfirst($meeple['type']);
      Stats::$statName($player, 1);
    }

    if (!empty($meepleIds)) {
      Engine::insertAsChild([
        'action' => RECEIVE,
        'args' => [
          'meeples' => $meepleIds,
          'player' => $player,
          'cardId' => $this->id,
        ],
      ]);
    }
  }
}
