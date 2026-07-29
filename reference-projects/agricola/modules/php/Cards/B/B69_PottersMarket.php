<?php
namespace AGR\Cards\B;
use AGR\Managers\Players;

class B69_PottersMarket extends \AGR\Models\MinorImprovement
{
  public function __construct($row)
  {
    parent::__construct($row);
    $this->id = 'B69_PottersMarket';
    $this->name = clienttranslate('Potters Market');
    $this->deck = 'B';
    $this->number = 69;
    $this->category = CROP_PROVIDER;
    $this->desc = [
      clienttranslate(
        'At any time, you can pay 3 <CLAY> and 2 <FOOD>. If you do, place 1 <VEGETABLE> on each of the next 2 round spaces. At the start of these rounds, you get the <VEGETABLE>.'
      ),
    ];
    $this->vp = 1;
    $this->cost = [
      WOOD => 2,
    ];
    $this->isArtifexOrBubulcus = true;
  }
  
  public function isListeningTo($event)
  {
    return $this->isAnytime($event);
  }
  
  public function onPlayerAtAnytime($player, $event)
  {
    return [
      'type' => NODE_SEQ,
      'countAsUse' => true,
      'childs' => [
	    $this->payNode([CLAY => 3, FOOD => 2]),
        $this->futureMeeplesNode([VEGETABLE => 1], 2),
      ]
	];
  }  
}
