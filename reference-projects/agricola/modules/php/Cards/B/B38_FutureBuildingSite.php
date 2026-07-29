<?php
namespace AGR\Cards\B;
use AGR\Core\Globals;
use AGR\Models\PlayerBoard;

class B38_FutureBuildingSite extends \AGR\Models\MinorImprovement
{
  public function __construct($row)
  {
    parent::__construct($row);
    $this->id = 'B38_FutureBuildingSite';
    $this->name = clienttranslate('Future Building Site');
    $this->deck = 'B';
    $this->number = 38;
    $this->category = POINTS_PROVIDER;
    $this->desc = [
      clienttranslate(
        'Up until all other farmyard spaces are used, you cannot use the unused spaces that are orthogonally adjacent to your house (not even to build rooms).'
      ),
    ];
    $this->vp = 3;
    $this->prerequisite = clienttranslate('Play in Round 4 or Before');
    $this->isArtifexOrBubulcus = true;
    $this->bannedWeak1or2p = true;
  }

  public function isBuyable($player, $ignoreResources = false, $args = [])
  {
    if (Globals::getTurn() > 4) {
      return false;
    }

    return parent::isBuyable($player, $ignoreResources, $args);
  }

  public function isListeningTo($event)
  {
    return $this->isActionEvent($event, 'Plow') ||
      $this->isActionEvent($event, 'Construct') ||
      $this->isActionEvent($event, 'Fencing') ||
      $this->isActionEvent($event, 'Stables');
  }

  public function onBuy($player)
  {
    $lockedSpaces = [];
    foreach($player->board()->getFreeZones() as $zone) {
      if($player->board()->isAdjacentToType($zone['x'],$zone['y'],$player->getRoomType())){
        $lockedSpaces[] = $zone;
      }
    }
    $this->setExtraDatas('locked',$lockedSpaces);
  }

  private function checkLocked($locations)
  {
    $touchLocked = false;
    foreach($locations as $location){
      foreach($this->getExtraDatas('locked') as $locked){
        if($locked['x'] == $location['x'] && $locked['y'] == $location['y']){
          $touchLocked = true;
        }
      }
    }
    if($touchLocked == false){
      return;
    }
    foreach($this->getPlayer()->board()->getFreeZones() as $zone) {
      $freeInLock = false;
      foreach($this->getExtraDatas('locked') as $locked){
        if($locked['x'] == $zone['x'] && $locked['y'] == $zone['y']){
          $freeInLock = true;
        }
      }
      if($freeInLock == false){
        throw new \BgaVisibleSystemException('Up until all other farmyard spaces are used, you cannot use the unused spaces that are orthogonally adjacent to your house');
      }
    }
    return;
  }

  public function onPlayerAfterPlow($player, $event)
  {
    $locations = [];
    $locations[] = $event['field'];
    $this->checkLocked($locations);
    return;
  }

  public function onPlayerAfterConstruct($player, $event)
  {
    $this->checkLocked($event['rooms']);
    return;
  }

  public function onPlayerAfterFencing($player, $event)
  {
    $pastures = $event['newPastures']['new'];
    $locations = [];
    foreach ($pastures as $pasture) {
      foreach ($pasture['nodes'] as $node) {
        $locations[] = $node;
      }
    }
    $this->checkLocked($locations);
    return;
  }

  public function onPlayerAfterStables($player, $event)
  {
    $this->checkLocked($event['stables']);
    return;
  }

}