<?php
namespace AGR\Cards\E;
use AGR\Helpers\Utils;
use AGR\Managers\Scores;

class E127_DiligentFarmer extends \AGR\Models\Occupation
{
  public function __construct($row)
  {
    parent::__construct($row);
    $this->id = 'E127_DiligentFarmer';
    $this->name = clienttranslate('Diligent Farmer');
    $this->deck = 'E';
    $this->author = 'shiunyuans';
    $this->number = 127;
    $this->category = 'FARMYARD_-_PLACE_FOR_PERSON';
    $this->desc = [
      clienttranslate(
        'When you play this card, if you would score the maximum 4 points in 3 scoring categories (including fenced stables), you can extend your house by 1 room at no cost.'
      ),
    ];
    $this->players = '3+';
  }

  public function onBuy($player) 
  {
    $scores = Scores::compute();

    foreach ($scores as $pId => $score) {
      $max = 0;

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
              $max++;
            }
          }
        }
      }

      if ($max >= 3) {
        return [
          'action' => CONSTRUCT,
          'optional' => true,
          'args' => [
            'costs' => Utils::formatCost(['max' => 1]),
            'max' => 1,
            'source' => $this->name,
            'trueAction' => false
          ],
          'source' => $this->name,
        ];
      }
    }
  }
}
