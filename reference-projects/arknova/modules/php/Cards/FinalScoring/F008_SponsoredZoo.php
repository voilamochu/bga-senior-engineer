<?php

namespace ARK\Cards\FinalScoring;

class F008_SponsoredZoo extends \ARK\Models\FinalScoring
{
  public function __construct($row)
  {
    parent::__construct($row);
    $this->supported = [BASE];
    $this->id = 'F008_SponsoredZoo';
    $this->number = 8;
    $this->name = clienttranslate('Sponsored Zoo');
    $this->icon = 'SPONSOR-CARD';
    $this->desc = clienttranslate('Gain <CONSERVATION> for **sponsor cards** in your zoo');
    $this->scoreMap = [3 => 1, 6 => 2, 8 => 3, 10 => 4];
  }

  public function getQuantity()
  {
    return $this->getPlayer()
      ->getPlayedCards(\CARD_SPONSOR)
      ->count();
  }
}
