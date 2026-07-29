<?php
namespace AGR\Cards\B;
use AGR\Helpers\Utils;

class B149_OpenAirFarmer extends \AGR\Models\Occupation
{
  public function __construct($row)
  {
    parent::__construct($row);
    $this->id = 'B149_OpenAirFarmer';
    $this->name = clienttranslate('Open Air Farmer');
    $this->deck = 'B';
    $this->number = 149;
    $this->category = FARM_PLANNER;
    $this->desc = [
      clienttranslate(
        'When you play this card, you remove exactly 3 <STABLE> in your supply from play to build a pasture covering 2 farmyard spaces. You only need to pay a total of 2 <WOOD> for fences'
      ),
    ];
    $this->players = '4+';
    $this->isArtifexOrBubulcus = true;

    $this->rulings = [
      clienttranslate('You may not build the removed stables later.'),
    ];
  }

  public function onBuy($player)
  {
    return [
      'type' => NODE_SEQ,
      'optional' => false,
      'childs' => [    
         $this->payNode([STABLE=>3]),
         [
      'action' => FENCING,
      'args' => ['openAirFarmer'=> true, 'max' => 6, 'costs' => Utils::formatCost([WOOD => 0])],
         ]
        
      ],
    ];
  }
}
