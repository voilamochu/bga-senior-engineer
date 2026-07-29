<?php
namespace AGR\Cards\D;

class D163_JourneymanBricklayer extends \AGR\Models\Occupation
{
  public function __construct($row)
  {
    parent::__construct($row);
    $this->id = 'D163_JourneymanBricklayer';
    $this->name = clienttranslate('Journeyman Bricklayer');
    $this->deck = 'D';
    $this->number = 163;
    $this->category = BUILDING_RESOURCE_PROVIDER;
    $this->desc = [
      clienttranslate(
        'When you play this card, you immediately get 2 <STONE>. Each time another player renovates to stone or builds a stone room, you get 1 <STONE>.'
      ),
    ];
    $this->players = '4+';
    $this->isCorbariusOrDulcinaria = true;
  }
  
  public function onBuy($player)
  {
    return $this->gainNode([STONE => 2]);
  }
  
  public function isListeningTo($event)
  {
    return 
      ($this->isActionEvent($event, 'Renovation', 'opponent') && $event['newRoomType'] == 'roomStone') ||
      ($this->isActionEvent($event, 'Construct', 'opponent') && $event['roomType'] == 'roomStone');
  }
  
  public function onOpponentAfterRenovation($player, $event)
  {
    return $this->gainNode([STONE => 1]);
  }
  
  public function onOpponentAfterConstruct($player, $event)
  {
    return $this->gainNode([STONE => 1]);
  }
}
