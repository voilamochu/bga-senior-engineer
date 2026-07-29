<?php
namespace AGR\Cards\D;
use AGR\Core\Globals;

class D153_WealthyMan extends \AGR\Models\Occupation
{
  public function __construct($row)
  {
    parent::__construct($row);
    $this->id = 'D153_WealthyMan';
    $this->name = clienttranslate('Wealthy Man');
    $this->deck = 'D';
    $this->number = 153;
    $this->category = POINTS_PROVIDER;
    $this->desc = [
      clienttranslate(
        'At the start of each of the 1st/2nd/3rd/4th/5th/6th harvest, if you have at least 1/2/3/4/5/6 grain fields, you get 1 bonus <SCORE>.'
      ),
    ];
    $this->players = '4+';
    $this->extraVp = true;
    $this->isCorbariusOrDulcinaria = true;
  }
  
  public function isListeningTo($event)
  {
    return $this->isPlayerEvent($event) && $event['type'] == 'StartHarvest';
  }

  public function onPlayerStartHarvest($player, $event)
  {
    $turn = Globals::getTurn();
    $map = [
      4 => 1, 
      7 => 2,
      9 => 3,
      11 => 4,
      13 => 5,
      14 => 6,
    ];

    $harvest = $map[$turn];
    $grainFields = count($player->board()->getGrainFields());

    if ($grainFields >= $harvest) {
      return $this->gainNode([SCORE => 1]);
    }
  }
}
