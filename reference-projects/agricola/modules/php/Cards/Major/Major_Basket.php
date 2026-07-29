<?php
namespace AGR\Cards\Major;

use AGR\Core\Notifications;
use AGR\Helpers\Utils;

class Major_Basket extends \AGR\Models\MajorImprovement
{
  public function __construct($row)
  {
    parent::__construct($row);
    $this->id = 'Major_Basket';
    $this->number = 10;
    $this->name = clienttranslate('Basketmaker\'s Workshop');
    $this->tooltip = [];
    $this->desc = [
      clienttranslate('[Harvest]'),
      '<REED> <ARROW-1X> 3<FOOD>',
      clienttranslate('[Scoring]'),
      '2/4/5<REED> <ARROW-1X> 1/2/3<SCORE>',
    ];

    $this->cost = [REED => 2, STONE => 2];
    $this->vp = 2;
    $this->extraVp = true;
    $this->exchanges = [
      Utils::formatExchange([REED => [FOOD => 3], 'max' => 1], clienttranslate('Basketmaker'), [HARVEST], $this->id),
    ];
    $this->scoresMap = [
      '2-3' => 1,
      '4' => 2,
      '5+' => 3,
    ];
  }

  public function computeBonusScore()
  {
    $bonus = $this->getBonusScore();
    if ($bonus != 0) {
      $this->addBonusScoringEntry($bonus);
    } else {
      $this->addResourceScoringEntry(REED, $this->scoresMap, '<REED>', '<REED>');
    }
  }

  public function onEndOfGame()
  {
    $meeples = $this->endOfGameCleanup(REED, $this->scoresMap);
    if ($meeples != null) {
      Notifications::endOfGame($this->getPlayer(), $meeples, $this->name);
    }
  }
}
