<?php
namespace AGR\Cards\B;
use AGR\Managers\Meeples;
use AGR\Managers\PlayerCards;
use AGR\Core\Notifications;
use AGR\Core\Stats;

class B48_ForestStone extends \AGR\Models\MinorImprovement
{
  public function __construct($row)
  {
    parent::__construct($row);
    $this->id = 'B48_ForestStone';
    $this->name = clienttranslate('Forest Stone');
    $this->deck = 'B';
    $this->number = 48;
    $this->category = FOOD_PROVIDER;
    $this->desc = [
      clienttranslate(
        'Place 2 <FOOD> on this card. Each time you use a wood accumulation space, move 1 of these <FOOD> to your supply. Each time you use a stone accumulation space, add 2 <FOOD> to this card.'
      ),
    ];
    $this->vp = 1;
    $this->costs = [[WOOD => 2], [STONE => 1]];
    $this->prerequisite = clienttranslate('1 Occupation');
    $this->occupationPrerequisites = ['min' => 1];
    $this->holder = true;
    $this->isArtifexOrBubulcus = true;
  }

  public function onBuy($player)
  {
    return $this->addFood();
  }

  public function isListeningTo($event)
  {
    return ($this->isBeforeCollectEvent($event, WOOD) && !is_null($this->getNextResource())) ||
      $this->isBeforeCollectEvent($event, STONE);
  }

  public function onPlayerPlaceFarmer($player, $event)
  {
    if ($this->isBeforeCollectEvent($event, WOOD)) {
      return $this->receiveNode($this->getNextResource()['id'], true);
    }
    else {
      return [
        'action' => SPECIAL_EFFECT,
        'args' => [
          'cardId' => $this->id,
          'method' => 'addFood',
        ],
      ];
    }
  }

  public function getAddFoodDescription()
  {
    return [
      'log' => clienttranslate('Add ${resources_desc} to ${card}'),
      'args' => [
        'resources_desc' => '2 <FOOD>',
        'card' => $this->name,
      ],
    ];
  }

  public function addFood()
  {
    $player = $this->getPlayer();

    $created = Meeples::createResourceInLocation(FOOD, $this->id, $player->getId(), null, null, 2);
    Notifications::accumulate(Meeples::getMany($created), true);
  }
}
