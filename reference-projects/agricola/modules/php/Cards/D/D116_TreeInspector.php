<?php
namespace AGR\Cards\D;
use AGR\Managers\Meeples;
use AGR\Core\Notifications;
use AGR\Core\Globals;

class D116_TreeInspector extends \AGR\Models\PlayerActionCard
{
  protected $type = OCCUPATION;
  public function __construct($row)
  {
    parent::__construct($row);
    $this->id = 'D116_TreeInspector';
    $this->name = clienttranslate('Tree Inspector');
    $this->deck = 'D';
    $this->number = 116;
    $this->category = BUILDING_RESOURCE_PROVIDER;
    $this->desc = [
      clienttranslate(
        'This card is a __1 <WOOD>__ accumulation space for you only. Each time the newly revealed action space card is a __Quarry__ accumulation space, you must discard all <WOOD> from this card.'
      ),
    ];
    $this->players = '1+';
    $this->privateSpace = true;
    $this->accumulation = [WOOD => 1];
    $this->flow = [
      'action' => COLLECT,
    ];
    $this->isCorbariusOrDulcinaria = true;
    $this->bannedWeak1or2p = true;
    $this->bannedWeak3or4p = true;
  }

  public function hasAccumulation()
  {
    if (count($this->accumulation) == 0) {
      return false;
    }
    return true;
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
    return $this->isPlayerEvent($event) && 
      $event['type'] == 'AfterRevealAction' &&
      (Globals::getLastRevealed() == 'ActionWesternQuarry' || 
       Globals::getLastRevealed() == 'ActionEasternQuarry');
  }

  public function onPlayerAfterRevealAction($player, $event)
  {
    return [
      'action' => SPECIAL_EFFECT,
      'args' => [
        'cardId' => $this->id,
        'method' => 'removeWood',
        'args' => [],        
      ],
    ];
  }

  public function removeWood()
  {
    $meeples = Meeples::getResourcesOnCard($this->id, null, WOOD);
    if ($meeples->count() > 0) {
      $silentKill = Meeples::deleteResources($meeples);
      Notifications::silentKill($silentKill);
    }
  }
}
