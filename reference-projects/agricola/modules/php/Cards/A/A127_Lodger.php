<?php
namespace AGR\Cards\A;
use AGR\Core\Globals;
use AGR\Managers\Farmers;
use AGR\Helpers\CardRulings;

class A127_Lodger extends \AGR\Models\Occupation
{
  public function __construct($row)
  {
    parent::__construct($row);
    $this->id = 'A127_Lodger';
    $this->name = clienttranslate('Lodger');
    $this->deck = 'A';
    $this->number = 127;
    $this->category = FARM_PLANNER;
    $this->desc = [
      clienttranslate(
        'This card provides room for one person, but only until the returning home phase of round 9. If, by then, there is no room elsewhere for that person, remove it from play.'
      ),
    ];
    $this->players = '3+';  
    $this->holder = true;
    $this->isArtifexOrBubulcus = true;
    $this->bannedStrong3or4p = true;

    $this->rulings = CardRulings::fromKeys([
      'NEVER_FIVE_FARMERS',
    ]);
  }
  public function isListeningTo($event)
  {
    return $this->isPlayerEvent($event) && 
	  $event['type'] == 'StartReturnHome' && Globals::getTurn() == 9;
  }
  public function hasRoom() {
    if($this->getExtraDatas('hasRoom')==1 && Globals::getTurn() <= 9){
      return true;
    }
    return false;
  }

  public function onPlayerStartReturnHome($player,$event) {
    if($this->getExtraDatas('hasRoom')==1 && ($player->countRooms() + $player->countExtraRoom(false) <  $player->countFarmers())){
      Farmers::setFarmerState(Farmers::get($player->getFarmers()->first()['id']), REMOVE);
    }
    $this->setExtraDatas('hasRoom',0);
    return;
  }
  public function onBuy($player)
  {
    if( Globals::getTurn() <= 9){
      $this->setExtraDatas('hasRoom',1);
    }
    return;
  }
}
