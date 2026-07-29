<?php

namespace AGR\Cards\B;
use AGR\Models\Occupation;

class B167_StableSergeant extends Occupation
{
  public function __construct($row)
  {
    parent::__construct($row);
    $this->id = 'B167_StableSergeant';
    $this->name = clienttranslate('Stable Sergeant');
    $this->deck = 'B';
    $this->number = 167;
    $this->category = LIVESTOCK_PROVIDER;
    $this->desc = [
      clienttranslate(
        'When you play this card, you can pay 2 <FOOD> to get 1 <SHEEP>, 1 <PIG>, and 1 <CATTLE>, but only if you can accommodate all three animals on your farm.'
      ),
    ];
    $this->players = '4+';
    $this->isArtifexOrBubulcus = true;
  }

  public function onBuy($player)
  {
    if ($player->board()->canAccommodateAll([SHEEP, PIG, CATTLE])) {
      return $this->payGainNode([FOOD => 2], [SHEEP => 1, PIG => 1, CATTLE => 1]);
    }
  }
}
