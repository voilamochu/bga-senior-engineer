<?php
namespace AGR\Cards\B;

class B132_EstateMaster extends \AGR\Models\Occupation
{
  public function __construct($row)
  {
    parent::__construct($row);
    $this->id = 'B132_EstateMaster';
    $this->name = clienttranslate('Estate Master');
    $this->deck = 'B';
    $this->number = 132;
    $this->category = POINTS_PROVIDER;
    $this->desc = [
      clienttranslate(
        'Once you have no unused farmyard spaces left, you get 1 bonus <SCORE> for each <VEGETABLE> that you harvest.'
      ),
    ];
    $this->extraVp = true;
    $this->players = '3+';
    $this->isArtifexOrBubulcus = true;
    $this->bannedStrong3or4p = true;
  }
  public function isListeningTo($event)
  {
    return ($this->isActionEvent($event, 'Reap') && $this->countVegetables($event) > 0) || 
    (!$this->isFlagged() && ($this->isActionEvent($event, 'Construct') ||
        $this->isActionEvent($event, 'Stables') ||
        $this->isActionEvent($event, 'Fencing') || 
        $this->isActionEvent($event, 'Plow') ));
  }

  public function countVegetables($event)
  {
    $n = 0;
    foreach($event['crops'] as $meeple){
      $n += $meeple['type'] == \VEGETABLE? 1 : 0;
    }
    return $n;
  }

  public function onPlayerAfterReap($player, $event)
  {
    $n = $this->countVegetables($event);
    if ($this->isFlagged()) {
      return $this->gainNode([SCORE => $n]);
    }
    // in case some strange special action cause the zone is used but not by Construct/Stables/Fencing/Plow
    if(empty($player->board()->getFreeZones())){
      return [
        'type' => NODE_SEQ,
        'childs' => [
          $this->flagCardNode(),
          $this->gainNode([SCORE => $n])
        ]
      ];
    }
  }  

  function checkAllZoneUsed($player) {
    if(!empty($player->board()->getFreeZones())){
      return;
    }
    return $this->flagCardNode();
  }

  public function onPlayerAfterConstruct($player, $event)
  {
    return $this->checkAllZoneUsed($player);
  } 

  public function onPlayerAfterStables($player, $event)
  {
    return $this->checkAllZoneUsed($player);
  }

  public function onPlayerAfterFencing($player, $event)
  {
    return $this->checkAllZoneUsed($player);
  }

  public function onPlayerAfterPlow($player, $event)
  {
    return $this->checkAllZoneUsed($player);
  }
}
