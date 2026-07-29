<?php
namespace AGR\Cards\B;

class B36_Bottles extends \AGR\Models\MinorImprovement
{
  public function __construct($row)
  {
    parent::__construct($row);
    $this->id = 'B36_Bottles';
    $this->name = clienttranslate('Bottles');
    $this->deck = 'B';
    $this->number = 36;
    $this->category = POINTS_PROVIDER;
    $this->desc = [
      clienttranslate('For each person you have, you must pay an additional 1 <CLAY> and 1 <FOOD> to play this card.'),
    ];
    $this->vp = 4;
    $this->costText = clienttranslate('see below');
  }

  public function getBaseCosts()
  {
    $player = $this->getPlayer();
    $farmers = $player->countFarmers();
    return [[
      CLAY => $farmers,
      FOOD => $farmers,
    ]];
  }
}
