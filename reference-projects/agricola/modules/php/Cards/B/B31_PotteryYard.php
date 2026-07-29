<?php
namespace AGR\Cards\B;

class B31_PotteryYard extends \AGR\Models\MinorImprovement
{
  public function __construct($row)
  {
    parent::__construct($row);
    $this->id = 'B31_PotteryYard';
    $this->name = clienttranslate('Pottery Yard');
    $this->deck = 'B';
    $this->number = 31;
    $this->category = POINTS_PROVIDER;
    $this->desc = [
      clienttranslate(
        'During the scoring, if there are at least 2 orthogonally adjacent unused spaces in your farm, you get 2 bonus <SCORE>. (You still get the negative points for those unused spaces.'
      ),
    ];
    $this->vp = 1;
    $this->extraVp = true;
    $this->prerequisite = clienttranslate('Pottery (or an Upgrade Thereof)');
    $this->isArtifexOrBubulcus = true;
  }

  public function isBuyable($player, $ignoreResources = false, $args = [])
  {
    if (!($player->hasPlayedCard('Major_Pottery') || $player->hasPlayedCard('D60_LargePottery'))) {
      return false;
    }
    return parent::isBuyable($player, $ignoreResources, $args);
  }
  
  public function computeBonusScore()
  {
    $bonus = 0;
    $player = $this->getPlayer();
    foreach( $player->board()->getFreeZones() as $zone) {
      if($player->board()->isAdjacentToType($zone['x'],$zone['y'],'free')){
        $bonus = 2;
        
      }
    }
    $this->addBonusScoringEntry($bonus);
  }
}
