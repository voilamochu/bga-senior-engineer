<?php
namespace AGR\Cards\B;
use AGR\Core\Globals;

class B134_HousebookMaster extends \AGR\Models\Occupation
{
  public function __construct($row)
  {
    parent::__construct($row);
    $this->id = 'B134_HousebookMaster';
    $this->name = clienttranslate('Housebook Master');
    $this->deck = 'B';
    $this->number = 134;
    $this->category = POINTS_PROVIDER;
    $this->desc = [
      clienttranslate(
        'After playing this card, if you renovate to stone in round 13/12/11 or before, you immediately get 1/2/3 <FOOD> and 1/2/3 bonus <SCORE>.'
      ),
    ];
    $this->players = '3+';
    $this->extraVp = true;
    $this->isArtifexOrBubulcus = true;
  }
    
  public function isListeningTo($event)
  {
    return $this->isActionEvent($event, 'Renovation') && $event['newRoomType'] == 'roomStone';
  }

  public function onPlayerAfterRenovation($player, $event)
  {
    $turn = Globals::getTurn();
    $n = 0;
    
    if ($turn <= 11) {$n = 3;}
    elseif ($turn == 12) {$n = 2;}
    elseif ($turn == 13) {$n = 1;}

    if ($n > 0) {
      return $this->gainNode([FOOD => $n, SCORE => $n]);
    }
  }
}
