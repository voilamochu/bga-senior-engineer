<?php
namespace AGR\Cards\C;
use AGR\Core\Notifications;
use AGR\Managers\Meeples;

class C81_MaterialHub extends \AGR\Models\MinorImprovement
{
  public function __construct($row)
  {
    parent::__construct($row);
    $this->id = 'C81_MaterialHub';
    $this->name = clienttranslate('Material Hub');
    $this->deck = 'C';
    $this->number = 81;
    $this->category = BUILDING_RESOURCE_PROVIDER;
    $this->desc = [
      clienttranslate(
        'Immediately place 2 of each building resource on this card. Each time any player (including you) takes at least 5 <WOOD>, 4 <CLAY>, 3 <REED>, or 3 <STONE>, you get 1 of that building resource from this card.'
      ),
    ];
    $this->cost = [
      WOOD => 1,
      CLAY => 1,
    ];
    $this->prerequisite = ('1 reed and 1 stone in your supply');
    $this->holder = true;
    $this->isCorbariusOrDulcinaria = true;
  }

  public function onBuy($player)
  {
    $types = [WOOD, WOOD, CLAY, CLAY, REED, REED, STONE, STONE];

    $created = [];
    foreach ($types as $i => $type) {
      $created = array_merge(
        $created,
        Meeples::createResourceInLocation($type, $this->id, $player->getId(), null, null, 1, $i)
      );
    }

    Notifications::accumulate(Meeples::getMany($created), true);
  }

  public function isBuyable($player, $ignoreResources = false, $args = [])
  {
    $reed = $player->countReserveResource(REED);
    $stone = $player->countReserveResource(STONE);

    if ($reed == 0 || $stone == 0) {
      return false;
    }

    return parent::isBuyable($player, $ignoreResources, $args);
  }

  public function isListeningTo($event)
  {
    return $this->isCollectEvent($event, null, false, null);
  }

  public function onAfterCollect($player, $event)
  {
    $resources = [WOOD, CLAY, REED, STONE];
    $threshold = [
      WOOD => 5,
      CLAY => 4,
      REED => 3,
      STONE => 3,
    ];
    $childs = [];

    $meeples = [WOOD => 0, CLAY => 0, REED => 0, STONE => 0];
    foreach ($event['meeples'] as $meeple) {
      $type = $meeple['type'];
      if (\array_key_exists($type, $meeples)) {
        $meeples[$type]++;
      }
    }

    foreach ($resources as $resource) {
      if ($meeples[$resource] >= $threshold[$resource]) {
        $meeple = $this->getNextResource($resource);
        if (!is_null($meeple)) {
          $childs[] = $this->receiveNode($meeple['id'], true, $this->getPlayer());
        }
      }
    }

    if ($childs != []) {
      return [
        'type' => NODE_SEQ,
        'pId' => $this->pId,
        'childs' => $childs
      ];
    }
  }

  public function onPlayerAfterCollect($player, $event)
  {
    return $this->onAfterCollect($player, $event);
  }

  public function onOpponentCollect($player, $event)
  {
    return $this->onAfterCollect($player, $event);
  }
}
