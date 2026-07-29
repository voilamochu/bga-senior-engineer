<?php

namespace AGR\Cards\C;

use AGR\Models\MinorImprovement;

class C40_CanvasSack extends MinorImprovement
{
  public function __construct($row)
  {
    parent::__construct($row);
    $this->id = 'C40_CanvasSack';
    $this->name = clienttranslate('Canvas Sack');
    $this->deck = 'C';
    $this->number = 40;
    $this->category = GOODS_PROVIDER;
    $this->desc = [
      clienttranslate('When you play this card paying <GRAIN>/<REED> for it, you immediately get 1 <VEGETABLE>/4 <WOOD>.'),
    ];
    $this->vp = 1;
    // No costs, these are check in "onBuy" and gain payGainNodes are created
    // Cost display for card is added in Card.js
    // $this->costs = [[GRAIN => 1], [REED => 1]];
    $this->prerequisite = clienttranslate('No Occupations');
    $this->occupationPrerequisites = ['max' => 0];
    $this->isCorbariusOrDulcinaria = true;
  }

  public function onBuy($player)
  {
    return [
      'type' => NODE_XOR,
      'optional' => false,
      'childs' => [
        $this->payGainNode([GRAIN => 1], [VEGETABLE => 1], null, false),
        $this->payGainNode([REED => 1], [WOOD => 4], null, false),
      ]
    ];
  }
}
