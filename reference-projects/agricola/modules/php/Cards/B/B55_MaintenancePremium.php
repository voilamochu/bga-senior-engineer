<?php
namespace AGR\Cards\B;
use AGR\Managers\Meeples;
use AGR\Managers\PlayerCards;
use AGR\Core\Notifications;
use AGR\Core\Stats;

class B55_MaintenancePremium extends \AGR\Models\MinorImprovement
{
  public function __construct($row)
  {
    parent::__construct($row);
    $this->id = 'B55_MaintenancePremium';
    $this->name = clienttranslate('Maintenance Premium');
    $this->deck = 'B';
    $this->number = 55;
    $this->category = FOOD_PROVIDER;
    $this->desc = [
      clienttranslate(
        'Place 3 <FOOD> on this card. Each time you use a wood accumulation space, you get 1 <FOOD> from this card. Each time you renovate restock this card to 3 <FOOD>.'
      ),
    ];
    $this->prerequisite = clienttranslate('2 Occupations');
    $this->occupationPrerequisites = ['min' => 2];
    $this->holder = true;
    $this->isArtifexOrBubulcus = true;
  }
  
  public function onBuy($player)
  {
    return $this->addFood(3);
  }
  
  public function isListeningTo($event)
  {
    return ($this->isBeforeCollectEvent($event, WOOD) && !is_null($this->getNextResource())) || 
      $this->isActionEvent($event, 'Renovation');
  }

  public function onPlayerPlaceFarmer($player, $event)
  {
    return [
      'action' => SPECIAL_EFFECT,
      'args' => [
        'cardId' => $this->id,
        'method' => 'gainNextResource',
      ],
    ];
  }
  
  public function onPlayerAfterRenovation($player, $event)
  {
    $n = 3 - Meeples::getResourcesOnCard($this->id, null, FOOD)->count();
    
    if ($n > 0) {
      return [
        'action' => SPECIAL_EFFECT,
        'args' => [
          'cardId' => $this->id,
          'method' => 'addFood',
          'args' => [$n],
        ],
      ];      
    }
  }

  public function getAddFoodDescription($n)
  {
    return [
      'log' => clienttranslate('Add ${resources_desc} to ${card}'),
      'args' => [
        'resources_desc' => strval($n) . ' <FOOD>',
        'card' => $this->name,
      ],
    ];
  }

  public function addFood($n)
  {
    $player = $player = $this->getPlayer();  
      
    $created = Meeples::createResourceInLocation(FOOD, $this->id, $player->getId(), null, null, $n);
    Notifications::accumulate(Meeples::getMany($created), true);      
  }
  
  public function getGainNextResourceDescription()
  {
    return [
      'log' => clienttranslate('Gain ${resources_desc}'),
      'args' => [
        'resources_desc' => '1 <FOOD>',
        'card' => $this->name,
      ],
    ];
  }

  public function gainNextResource()
  {
    $player = $this->getPlayer();
    $meeple = $this->getNextResource();
    Meeples::receiveResource($player, $meeple);
    Notifications::receiveResource($player, $meeple);
    Stats::incCardsFood($player, 1);
  }  
}
