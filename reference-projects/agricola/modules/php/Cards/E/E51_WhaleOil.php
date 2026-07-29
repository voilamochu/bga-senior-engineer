<?php
namespace AGR\Cards\E;
use AGR\Managers\Meeples;
use AGR\Core\Notifications;

class E51_WhaleOil extends \AGR\Models\MinorImprovement
{
  public function __construct($row)
  {
    parent::__construct($row);
    $this->id = 'E51_WhaleOil';
    $this->name = clienttranslate('Whale Oil');
    $this->deck = 'E';
    $this->author = 'chriss';
    $this->number = 51;
    $this->category = FOOD;
    $this->desc = [
      clienttranslate(
        'Each time you use __Fishing__, place 1 <FOOD> from the general supply on this card. Each time before you play an occupation, you get <FOOD> equal to the amount on this card.'
      ),
    ];
    $this->cost = [
      WOOD => 1,
    ];
    $this->holder = true;
  }

  public function isListeningTo($event)
  {
    return $this->isActionCardEvent($event, 'Fishing') ||
      ($event['type'] == 'action' &&
      $event['action'] == 'Occupation' &&
      $this->pId == $event['pId'] &&
      $event['method'] == 'beforeOccupation');
  }

  public function onPlayerPlaceFarmer($player, $event)
  {
    return [
      'action' => SPECIAL_EFFECT,
      'args' => [
        'cardId' => $this->id,
        'method' => 'addFood',
      ]
    ];
  }

  public function onPlayerBeforeOccupation($player, $event)
  {
    $n = Meeples::getResourcesOnCard($this->id, null, FOOD)->count();

    if ($n > 0) {
      return $this->gainNode([FOOD => $n]);
    }
  }

  public function getAddFoodDescription()
  {
    return [
      'log' => clienttranslate('Add ${resources_desc} to ${card}'),
      'args' => [
        'resources_desc' => '1 <FOOD>',
        'card' => $this->name,
      ],
    ];
  }

  public function addFood()
  {
    $player = $this->getPlayer();

    $created = Meeples::createResourceInLocation(FOOD, $this->id, $player->getId(), null, null, 1);
    Notifications::accumulate(Meeples::getMany($created), true);
  }

  public function onPlayerIsDoable($player, &$args)
  {
    if ($args['isDoable']) {
      return;
    }

    if ($args['action'] == OCCUPATION && Meeples::getResourcesOnCard($this->id, null, FOOD)->count() > 0) {
      $args['isDoable'] = true;
    }
  }
}
