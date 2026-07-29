<?php
namespace AGR\Cards\B;
use AGR\Managers\Farmers;

class B160_PubOwner extends \AGR\Models\Occupation
{
  public function __construct($row)
  {
    parent::__construct($row);
    $this->id = 'B160_PubOwner';
    $this->name = clienttranslate('Pub Owner');
    $this->deck = 'B';
    $this->number = 160;
    $this->category = CROP_PROVIDER;
    $this->desc = [
      clienttranslate(
        'When you play this card and at the end of each work phase in which the __Forest__, __Clay Pit__, and __Reed Bank__ accumulation spaces are all occupied, you get 1 <GRAIN>.'
      ),
    ];
    $this->players = '4+';
    $this->isArtifexOrBubulcus = true;
  }
  
  public function onBuy($player)
  {
    return $this->gainNode([GRAIN => 1]);
  }
  
  public function isListeningTo($event)
  {
    return $this->isPlayerEvent($event) && 
	  $event['type'] == 'EndWorkPhase';
  }
  
  public function onPlayerEndWorkPhase($player, $event)
  {
    if ((!Farmers::getOnCard('ActionForest')->empty()) && 
        (!Farmers::getOnCard('ActionClayPit')->empty()) &&
        (!Farmers::getOnCard('ActionReedBank')->empty()))
    {
      return $this->gainNode([GRAIN => 1]);
    }
  }
}
