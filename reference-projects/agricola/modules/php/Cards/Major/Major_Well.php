<?php
namespace AGR\Cards\Major;

class Major_Well extends \AGR\Models\MajorImprovement
{
  public function __construct($row)
  {
    parent::__construct($row);
    $this->id = 'Major_Well';
    $this->number = 5;
    $this->name = clienttranslate('Well');
    $this->desc = [
      clienttranslate('[Put 1 <FOOD> on the 5 next turns. At the start of each turn, collect the <FOOD>]'),
    ];
    $this->cost = [WOOD => 1, STONE => 3];
    $this->vp = 4;
  }

  protected function onBuy($player)
  {
    return $this->futureMeeplesNode([FOOD => 1], 5);
  }
}
