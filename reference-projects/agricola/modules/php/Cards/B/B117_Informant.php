<?php
namespace AGR\Cards\B;

class B117_Informant extends \AGR\Models\Occupation
{
  public function __construct($row)
  {
    parent::__construct($row);
    $this->id = 'B117_Informant';
    $this->name = clienttranslate('Informant');
    $this->deck = 'B';
    $this->number = 117;
    $this->category = BUILDING_RESOURCE_PROVIDER;
    $this->desc = [
      clienttranslate(
        'When you play this card, you immediately get 1 <WOOD>. After each work phase, if you have more <STONE> than <CLAY> in your supply, you get 1 <WOOD>.'
      ),
    ];
    $this->players = '1+';
    $this->isArtifexOrBubulcus = true;
    $this->bannedStrong1or2p = true;
    $this->bannedStrong3or4p = true;
  }
  
  public function onBuy($player)
  {
    return $this->gainNode([WOOD => 1]);
  }
  
  public function isListeningTo($event)
  {
    return $this->isPlayerEvent($event) && 
	  $event['type'] == 'AfterWorkPhase';
  }
  
  public function onPlayerAfterWorkPhase($player, $event)
  {
    $stone = $player->countReserveResource(STONE);
    $clay = $player->countReserveResource(CLAY);
    
    if ($stone > $clay) {
      return $this->gainNode([WOOD => 1]);
    }
  }
}
