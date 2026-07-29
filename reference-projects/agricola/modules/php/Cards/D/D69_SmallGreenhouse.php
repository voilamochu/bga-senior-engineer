<?php

namespace AGR\Cards\D;

use AGR\Helpers\Utils;
use AGR\Models\MinorImprovement;

class D69_SmallGreenhouse extends MinorImprovement
{
  public function __construct($row)
  {
    parent::__construct($row);
    $this->id = 'D69_SmallGreenhouse';
    $this->name = clienttranslate('Small Greenhouse');
    $this->deck = 'D';
    $this->number = 69;
    $this->category = CROP_PROVIDER;
    $this->desc = [
      clienttranslate(
        'Add 4 and 7 to the current round and place 1 <VEGETABLE> on each corresponding round space. At the start of these rounds, you can buy the <VEGETABLE> for 1 <FOOD>.'
      ),
    ];
    $this->vp = 1;
    $this->cost = [
      WOOD => 2,
    ];
    $this->prerequisite = clienttranslate('1 Occupation');
    $this->occupationPrerequisites = ['min' => 1];
  }

  public function onBuy($player)
  {
    return $this->futureMeeplesNode([VEGETABLEPLUS => 1], ['+4', '+7'], true, $this->id);
  }

  public function getReceiveFlow($meeple)
  {
    return $this->getReceiveFlowWithCost(
      $meeple,
      Utils::formatCost([FOOD => 1]),
      clienttranslate('Small Greenhouse effect')
    );
  }
}
