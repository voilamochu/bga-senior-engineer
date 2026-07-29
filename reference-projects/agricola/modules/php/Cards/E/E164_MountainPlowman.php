<?php
namespace AGR\Cards\E;

class E164_MountainPlowman extends \AGR\Models\Occupation
{
  public function __construct($row)
  {
    parent::__construct($row);
    $this->id = 'E164_MountainPlowman';
    $this->name = clienttranslate('Mountain Plowman');
    $this->deck = 'E';
    $this->author = 'nwoll';
    $this->number = 164;
    $this->category = 'ANIMALS_-_SHEEP';
    $this->desc = [
      clienttranslate('Each time you plow at least 1 field, you get 1 <SHEEP> for each field that you just plowed.'),
    ];
    $this->players = '4+';
  }

  public function isListeningTo($event)
  {
    return $this->isActionEvent($event, 'Plow');
  }

  public function onPlayerAfterPlow($player, $event)
  {
    return $this->gainNode([SHEEP => 1]);
  }
}
