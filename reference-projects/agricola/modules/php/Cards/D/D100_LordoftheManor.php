<?php
namespace AGR\Cards\D;
use AGR\Managers\Players;

class D100_LordoftheManor extends \AGR\Models\Occupation
{
  public function __construct($row)
  {
    parent::__construct($row);
    $this->id = 'D100_LordoftheManor';
    $this->name = clienttranslate('Lord of the Manor');
    $this->deck = 'D';
    $this->number = 100;
    $this->category = POINTS_PROVIDER;
    $this->desc = [
      clienttranslate(
        'During scoring, you get 1 bonus <SCORE> for each scoring category in which you score the maximum 4 points. (The bonus point is also awarded for 4 fenced stables.)'
      ),
    ];
    $this->players = '1+';
    $this->extraVp = true;
    $this->isCorbariusOrDulcinaria = true;
  }

  public function computeSpecialScore($scores)
  {
    foreach ($scores as $pId => $score) {
      $bonus = 0;

      if ($pId != $this->getPlayer()->getId()) {
        continue;
      }

      foreach ($score as $type => $values) {
        if (!in_array($type, ['fields', 'pastures', 'grains', 'vegetables', 'sheeps', 'pigs', 'cattles', 'stables'])) {
          continue;
        }

        if (isset($values['entries'])) {
          foreach ($values['entries'] as $entry) {
            if ($entry['score'] == 4) {
              $bonus++;
            }
          }
        }
      }

      if ($bonus > 0) {
        $this->addBonusScoringEntry($bonus, null, Players::get($pId));
      }
    }
  }
}
