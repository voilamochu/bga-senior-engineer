<?php
namespace AGR\Cards\E;

class E49_Twibil extends \AGR\Models\MinorImprovement
{
  public function __construct($row)
  {
    parent::__construct($row);
    $this->id = 'E49_Twibil';
    $this->name = clienttranslate('Twibil');
    $this->deck = 'E';
    $this->author = 'chriss';
    $this->number = 49;
    $this->category = FOOD;
    $this->desc = [
      clienttranslate('Each time after any player (including you) builds at least 1 wood room, you get 1 <FOOD>.'),
    ];
    $this->vp = 1;
    $this->cost = [
      STONE => 1,
    ];
  }

  public function isListeningTo($event)
  {
    return $this->isActionEvent($event, 'Construct',null) &&
	  $event['roomType'] == 'roomWood';
  }

  public function onAfterConstruct($player, $event)
  {
    return $this->gainNode([FOOD => 1]);
  }
}
