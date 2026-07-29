<?php
namespace AGR\Cards\E;
use AGR\Managers\Meeples;
use AGR\Core\Notifications;

class E52_Cubbyhole extends \AGR\Models\MinorImprovement
{
  public function __construct($row)
  {
    parent::__construct($row);
    $this->id = 'E52_Cubbyhole';
    $this->name = clienttranslate('Cubbyhole');
    $this->deck = 'E';
    $this->author = 'minnie';
    $this->number = 52;
    $this->category = FOOD;
    $this->desc = [
      clienttranslate(
        'For each room that you add to your house, place 1 <FOOD> from the general supply on this card. At the start of each feeding phase, you get <FOOD> equal to the amount on this card.'
      ),
    ];
    $this->vp = 1;
    $this->fee = [REED => 1];
    $this->costs = [[WOOD => 1], [CLAY => 1]];
    $this->holder = true;
  }

  public function isListeningTo($event)
  {
    return $this->isActionEvent($event, 'Construct', 'player') ||
      ($this->isPlayerEvent($event) && $event['type'] == 'StartHarvestFeedingPhase');
  }

  public function onPlayerAfterConstruct($player, $event)
  {
    $n = count($event['rooms']);

    return [
      'action' => SPECIAL_EFFECT,
      'args' => [
        'cardId' => $this->id,
        'method' => 'addFood',
        'args' => [$n]
      ]
    ];
  }

  public function onPlayerStartHarvestFeedingPhase($player)
  {
    $n = count(Meeples::getResourcesOnCard($this->id, null, FOOD));

    if ($n > 0) {
      return $this->gainNode([FOOD => $n]);
    }
  }

  public function getAddFoodDescription($n)
  {
    return [
      'log' => clienttranslate('Add ${resources_desc} to ${card}'),
      'args' => [
        'resources_desc' => $n . ' <FOOD>',
        'card' => $this->name,
      ],
    ];
  }

  public function addFood($n)
  {
    $player = $this->getPlayer();

    $created = Meeples::createResourceInLocation(FOOD, $this->id, $player->getId(), null, null, $n);
    Notifications::accumulate(Meeples::getMany($created), true);
  }
}
