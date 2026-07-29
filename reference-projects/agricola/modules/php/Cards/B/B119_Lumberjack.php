<?php
namespace AGR\Cards\B;
use AGR\Managers\Fences;
use AGR\Managers\Players;

class B119_Lumberjack extends \AGR\Models\Occupation
{
  public function __construct($row)
  {
    parent::__construct($row);
    $this->id = 'B119_Lumberjack';
    $this->name = clienttranslate('Lumberjack');
    $this->deck = 'B';
    $this->number = 119;
    $this->category = BUILDING_RESOURCE_PROVIDER;
    $this->desc = [
      clienttranslate(
        'You immediately get 1 <WOOD>. Additionally, place 1 <WOOD> on each of the next round spaces, up to the number of fences you built. At the start of these rounds, you get the <WOOD>.'
      ),
    ];
    $this->players = '1+';
    $this->isArtifexOrBubulcus = true;
  }
  
  public function onBuy($player)
  {
    $n = Fences::getOnBoard($player->getId())->count();      
      
    return [
      'type' => NODE_SEQ,
      'childs' => [
        $this->gainNode([WOOD => 1]),
        $this->futureMeeplesNode([WOOD => 1], $n),
      ]
    ];
  }  
}
