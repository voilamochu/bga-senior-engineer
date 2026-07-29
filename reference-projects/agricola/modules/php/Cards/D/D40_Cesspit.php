<?php
namespace AGR\Cards\D;

class D40_Cesspit extends \AGR\Models\MinorImprovement
{
  public function __construct($row)
  {
    parent::__construct($row);
    $this->id = 'D40_Cesspit';
    $this->name = clienttranslate('Cesspit');
    $this->deck = 'D';
    $this->number = 40;
    $this->category = GOODS_PROVIDER;
    $this->desc = [
      clienttranslate(
        'Alternate placing 1 <CLAY> and 1 <PIG> on each remaining round space, starting with <CLAY>. At the start of these rounds, you get the respective good.'
      ),
    ];
    $this->vp = -1;
    $this->prerequisite = clienttranslate('2 Fields and 1 Occupation');
    $this->occupationPrerequisites = ['min' => 1];    
    $this->isCorbariusOrDulcinaria = true;
  }
  
  public function isBuyable($player, $ignoreResources = false, $args = [])
  {
    if ($player->board()->countFields() < 2) {
      return false;
    }

    return parent::isBuyable($player, $ignoreResources, $args);
  }
  
  public function onBuy($player)
  {
    return [
      'type' => NODE_SEQ,
      'childs' => [
        $this->futureMeeplesNode([CLAY => 1], ['+1', '+3', '+5', '+7', '+9', '+11', '+13']),
        $this->futureMeeplesNode([PIG => 1], ['+2', '+4', '+6', '+8', '+10', '+12']),
      ]
    ];
  }
}
