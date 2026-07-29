<?php
namespace AGR\Cards\E;

class E100_MuseumCaretaker extends \AGR\Models\Occupation
{
  public function __construct($row)
  {
    parent::__construct($row);
    $this->id = 'E100_MuseumCaretaker';
    $this->name = clienttranslate('Museum Caretaker');
    $this->deck = 'E';
    $this->author = 'kosherdill';
    $this->number = 100;
    $this->category = 'BONUS_POINTS_-_GET';
    $this->desc = [
      clienttranslate(
        'At the start of each work phase, if you have at least 1 <WOOD>, 1 <CLAY>, 1 <REED>, 1 <STONE>, 1 <GRAIN>, and 1 <VEGETABLE> in your supply, you get 1 bonus <SCORE>.'
      ),
    ];
    $this->extraVp = true;
    $this->players = '1+';
  }  

 public function isListeningTo($event)
  {
    return $this->isPlayerEvent($event) &&
      $event['type'] == 'startOfWork' &&
	  $this->getPlayer()->countReserveResource(WOOD) >= 1 &&
	  $this->getPlayer()->countReserveResource(CLAY) >= 1 &&
	  $this->getPlayer()->countReserveResource(REED) >= 1 &&
	  $this->getPlayer()->countReserveResource(STONE) >= 1 &&
	  $this->getPlayer()->countReserveResource(GRAIN) >= 1 &&
	  $this->getPlayer()->countReserveResource(VEGETABLE) >= 1;
  }

  public function onPlayerStartOfWork($player, $event)
  {
	return $this->gainNode([SCORE => 1]);
  }
}