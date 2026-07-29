<?php

namespace AGR\Cards\D;

use AGR\Models\MinorImprovement;

class D8_FernSeeds extends MinorImprovement
{
  public function __construct($row)
  {
    parent::__construct($row);
    $this->id = 'D8_FernSeeds';
    $this->name = clienttranslate('Fern Seeds');
    $this->deck = 'D';
    $this->number = 8;
    $this->category = CROP_PROVIDER;
    $this->desc = [clienttranslate('You get 2 <FOOD> and 1 <GRAIN>, which you must sow immediately.')];
    $this->passing = true;
    $this->prerequisite = clienttranslate('1 Empty and 2 Planted Fields');
    $this->isCorbariusOrDulcinaria = true;
  }

  public function isBuyable($player, $ignoreResources = false, $args = [])
  {
    $planted = $player->board()->countPlantedLogicalFields();
    $empty = $player->board()->countEmptyLogicalFields();

    if ($empty < 1 || $planted < 2) {
      return false;
    }

    return parent::isBuyable($player, $ignoreResources, $args);
  }

  public function onBuy($player)
  {
    return [
      'type' => NODE_SEQ,
      'childs' => [
        $this->gainNode([FOOD => 2, GRAIN => 1]),
        [
          'action' => SOW,
          'args' => [
            'max' => 1,
            'type' => GRAIN,
          ]
        ],
      ]
    ];
  }
}
