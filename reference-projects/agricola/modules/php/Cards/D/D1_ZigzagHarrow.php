<?php
namespace AGR\Cards\D;

class D1_ZigzagHarrow extends \AGR\Models\MinorImprovement
{
  public function __construct($row)
  {
    parent::__construct($row);
    $this->id = 'D1_ZigzagHarrow';
    $this->name = clienttranslate('Zigzag Harrow');
    $this->deck = 'D';
    $this->number = 1;
    $this->category = FARM_PLANNER;
    $this->desc = [clienttranslate('You can immediately plow 1 field such that it completes a "zigzag" pattern.')];
    $this->passing = true;
    $this->cost = [
      WOOD => '1',
    ];
    $this->prerequisite = clienttranslate('3 Fields in an \"L\" Shape');
    $this->isCorbariusOrDulcinaria = true;
  }

  public function isBuyable($player, $ignoreResources = false, $args = [])
  {
    $zigzag = $player->board()-> zigzag();
    if(count($zigzag) >0){
      $this->setExtraDatas('zigzag',$zigzag);
      return parent::isBuyable($player, $ignoreResources, $args);
    }
    return false;
  }

  public function onBuy($player)
  {
    return [
      'action' => PLOW,
      'optional' => true,
      'args' => ['location' => $this->getExtraDatas('zigzag')],
    ];
  }
}
