<?php
namespace AGR\Cards\E;

class E99_UncaringParents extends \AGR\Models\Occupation
{
  public function __construct($row)
  {
    parent::__construct($row);
    $this->id = 'E99_UncaringParents';
    $this->name = clienttranslate('Uncaring Parents');
    $this->deck = 'E';
    $this->author = 'wulf684';
    $this->number = 99;
    $this->category = 'BONUS_POINTS_-_GET';
    $this->desc = [clienttranslate('At the end of each harvest, if you live in a stone house, you get 1 bonus <SCORE>.')];
    $this->players = '1+';
    $this->extraVp = true;
  }

  public function isListeningTo($event)
  {
    return $this->isPlayerEvent($event) && $event['type'] == 'EndHarvest'&&$this->getPlayer()->getRoomType() == 'roomStone';

  }

  public function onPlayerEndHarvest($player)
  {
    return $this->gainNode([SCORE => 1]);
  }
}
