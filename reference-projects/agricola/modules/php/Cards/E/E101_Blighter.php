<?php
namespace AGR\Cards\E;
use AGR\Core\Globals;

class E101_Blighter extends \AGR\Models\Occupation
{
  public function __construct($row)
  {
    parent::__construct($row);
    $this->id = 'E101_Blighter';
    $this->name = clienttranslate('Blighter');
    $this->deck = 'E';
    $this->author = 'luki';
    $this->number = 101;
    $this->category = 'BONUS_POINTS_-_GET';
    $this->desc = [
      clienttranslate(
        'When you play this card, you get 1 bonus <SCORE> for each complete stage left to play. You may not play any more occupations.'
      ),
    ];
    $this->extraVp = true;
    $this->players = '1+';
  }

  public function onBuy($player)
  {
    $remainingTurns = 14 - Globals::getTurn();
    $scoreMap = [0, 1, 1, 2, 2, 3, 3, 4, 4, 4, 5];
    $toGain = $scoreMap[$remainingTurns] ?? 5;
     if ($toGain != 0) {
      return $this->gainNode([SCORE => $toGain]);
    }
  }

  public function isListeningTo($event)
  {
    return $event['type'] == 'action' &&
      $event['action'] == 'Occupation' &&
      $this->pId == $event['pId'] &&
      $event['method'] == 'beforeOccupation';
  }

  public function onPlayerBeforeOccupation($player, $event)
  {
    throw new \BgaVisibleSystemException('You may not play any more occupations (E101_Blighter)');
  }
}
