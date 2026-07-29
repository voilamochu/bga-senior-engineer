<?php
namespace AGR\Cards\Major;

use AGR\Helpers\Utils;
use AGR\Core\Notifications;

class Major_Joinery extends \AGR\Models\MajorImprovement
{
  public function __construct($row)
  {
    parent::__construct($row);
    $this->id = 'Major_Joinery';
    $this->number = 8;
    $this->name = clienttranslate('Joinery');
    $this->tooltip = [];
    $this->desc = [
      clienttranslate('[Harvest]'),
      '<WOOD> <ARROW-1X> 2<FOOD>',
      clienttranslate('[Scoring]'),
      '3/5/7<WOOD> <ARROW-1X> 1/2/3<SCORE>',
    ];

    $this->cost = [WOOD => 2, STONE => 2];
    $this->vp = 2;
    $this->extraVp = true;
    $this->exchanges = [Utils::formatExchange([WOOD => [FOOD => 2], 'max' => 1], $this->name, [HARVEST], $this->id)];

    $this->scoresMap = [
      '3-4' => 1,
      '5-6' => 2,
      '7+' => 3,
    ];
  }

  public function computeBonusScore()
  {
    $bonus = $this->getBonusScore();
    if ($bonus != 0) {
      $this->addBonusScoringEntry($bonus);
    } else {
      $this->addResourceScoringEntry(WOOD, $this->scoresMap, '<WOOD>', '<WOOD>');
    }
  }

  public function onEndOfGame()
  {
    $meeples = $this->endOfGameCleanup(WOOD, $this->scoresMap);
    if ($meeples != null) {
      Notifications::endOfGame($this->getPlayer(), $meeples, $this->name);
    }
  }
}