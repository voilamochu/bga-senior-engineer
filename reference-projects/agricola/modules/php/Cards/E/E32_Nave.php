<?php
namespace AGR\Cards\E;

class E32_Nave extends \AGR\Models\MinorImprovement
{
  public function __construct($row)
  {
    parent::__construct($row);
    $this->id = 'E32_Nave';
    $this->name = clienttranslate('Nave');
    $this->deck = 'E';
    $this->author = 'luki';
    $this->number = 32;
    $this->category = 'BONUS_POINTS_-_GET';
    $this->desc = [
      clienttranslate(
        'During scoring, you get 1 bonus <SCORE> for each of the 5 columns of your farmyard board containing at least one room.'
      ),
    ];
    $this->cost = [
      STONE => 2,
      REED => 1,
    ];
    $this->extraVp = true;
  }
  
  public function computeBonusScore()
  {
    $playerBoard = $this->getPlayer()->board();
    $rooms = $playerBoard->getRooms();
    $columns = [];

    foreach ($rooms as $room) {
      if (!in_array($room['x'], $columns)) {
        $columns[] = $room['x'];
      }
    }

    $bonus = count($columns);
    if ($bonus > 0) {
      $this->addBonusScoringEntry($bonus);      
    }
  }
}
