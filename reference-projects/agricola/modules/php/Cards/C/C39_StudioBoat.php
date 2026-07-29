<?php
namespace AGR\Cards\C;
use AGR\Managers\Meeples;
use AGR\Managers\Players;

class C39_StudioBoat extends \AGR\Models\PlayerActionCard
{
  protected $type = MINOR;
  public function __construct($row)
  {
    parent::__construct($row);
    $this->id = 'C39_StudioBoat';
    $this->name = clienttranslate('Studio Boat');
    $this->deck = 'C';
    $this->number = 39;
    $this->category = POINTS_PROVIDER;
    $this->desc = [
      clienttranslate(
        'Each time you use the __Traveling Players__ accumulation space, you also get 1 bonus <SCORE>. In games with 1-3 players, this card is considered __Traveling Players__ (same effect as __Fishing__).'
      ),
    ];
    $this->cost = [
      WOOD => 1,
    ];
    $this->extraVp = true;
    $this->prerequisite = clienttranslate('1 Occupation');
    $this->occupationPrerequisites = ['min' => 1];
    $this->accumulation = [FOOD => 1];
    $this->flow = [
      'action' => COLLECT,
    ];
    $this->isCorbariusOrDulcinaria = true;
  }

  public function hasAccumulation()
  {
    $playerCount = Players::count();

    if ($playerCount < 4) {
      return true;
    }
    return false;
  }

  public function getAccumulation()
  {
    return $this->accumulation;
  }

  public function accumulate()
  {
    $ids = [];
    if ($this->hasAccumulation()) {
      foreach ($this->accumulation as $resource => $amount) {
        $ids = array_merge($ids, Meeples::createResourceOnCard($resource, self::getId(), $amount));
      }
    }
    return $ids;
  }

  public function isListeningTo($event)
  {
    return ($this->isActionCardEvent($event, 'TravelingPlayers') ||
      ($this->isBeforeCollectEvent($event, FOOD) && $event['actionCardId'] == 'C39_StudioBoat'));
  }

  public function onPlayerPlaceFarmer($player, $event)
  {
    return $this->gainNode([SCORE => 1]);
  }
}
