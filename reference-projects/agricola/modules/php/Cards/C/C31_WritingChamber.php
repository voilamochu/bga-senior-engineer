<?php
namespace AGR\Cards\C;
use AGR\Managers\Players;

class C31_WritingChamber extends \AGR\Models\MinorImprovement
{
  public function __construct($row)
  {
    parent::__construct($row);
    $this->id = 'C31_WritingChamber';
    $this->name = clienttranslate('Writing Chamber');
    $this->deck = 'C';
    $this->number = 31;
    $this->category = POINTS_PROVIDER;
    $this->desc = [
      clienttranslate(
        'During scoring, you get a number of bonus <SCORE> equal to the total of negative points you have, to a maximum of 7 <SCORE>.'
      ),
    ];
    $this->cost = [
      WOOD => '2',
    ];
    $this->extraVp = true;
    $this->isCorbariusOrDulcinaria = true;
    $this->bannedStrong1or2p = true;
    $this->bannedStrong3or4p = true;
  }

  public function computeSpecialScore($scores)
  {
    foreach ($scores as $pId => $score) {
      if ($pId != $this->getPlayer()->getId()) {
        continue;
      }
      $bonus = 0;
      foreach ($score as $type => $values) {
        if ($type == 'total') {
          continue;
        }

        if (isset($values['entries'])) {
          foreach ($values['entries'] as $entry) {
            if ($entry['score'] < 0) {
              $bonus = $bonus - $entry['score'];
            }
          }
        }
      }

      if ($bonus > 0) {
        if ($bonus <= 7) {
          $this->addBonusScoringEntry($bonus, null, Players::get($pId));
        }
        else{
          $this->addBonusScoringEntry(7, null, Players::get($pId));
        }
      }
    }
  }
}