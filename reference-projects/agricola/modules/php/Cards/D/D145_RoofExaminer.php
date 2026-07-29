<?php

namespace AGR\Cards\D;

use AGR\Models\Occupation;

class D145_RoofExaminer extends Occupation
{
  public function __construct($row)
  {
    parent::__construct($row);
    $this->id = 'D145_RoofExaminer';
    $this->name = clienttranslate('Roof Examiner');
    $this->deck = 'D';
    $this->number = 145;
    $this->category = BUILDING_RESOURCE_PROVIDER;
    $this->desc = [
      clienttranslate(
        'When you play this card, if you have 1/2/3/4 major improvements, you immediately get 2/3/4/5 <REED>.'
      ),
    ];
    $this->players = '3+';
    $this->isCorbariusOrDulcinaria = true;
  }

  public function onBuy($player)
  {
    $n = min($player->getCards(MAJOR, true)->count() + 1, 5);
    if ($n == 1) {
      return;
    }

    if ($n > 0) {
      return $this->gainNode([REED => $n]);
    }
  }
}
