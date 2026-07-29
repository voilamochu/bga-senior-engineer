<?php
namespace AGR\Cards\B;

class B111_Rustic extends \AGR\Models\Occupation
{
  public function __construct($row)
  {
    parent::__construct($row);
    $this->id = 'B111_Rustic';
    $this->name = clienttranslate('Rustic');
    $this->deck = 'B';
    $this->number = 111;
    $this->category = FOOD_PROVIDER;
    $this->desc = [
      clienttranslate(
        'For each clay room you build, you get 2 <FOOD> and 1 bonus <SCORE>. (this does not apply to stone rooms and renovated wood rooms.)'
      ),
    ];
    $this->players = '1+';
    $this->extraVp = true;
    $this->isArtifexOrBubulcus = true;
  }
    
  public function isListeningTo($event)
  {
    return $this->isActionEvent($event, 'Construct') && 
	  $event['roomType'] == 'roomClay';
  }
  
  public function onPlayerAfterConstruct($player, $event)
  {
    $n = count($event['rooms']);
	
    return $this->gainNode([FOOD => 2 * $n, SCORE => $n]);
  }
}
