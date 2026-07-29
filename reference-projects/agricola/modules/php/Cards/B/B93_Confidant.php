<?php
namespace AGR\Cards\B;
use AGR\Core\Globals;
use AGR\Helpers\Utils;

class B93_Confidant extends \AGR\Models\Occupation
{
  public function __construct($row)
  {
    parent::__construct($row);
    $this->id = 'B93_Confidant';
    $this->name = clienttranslate('Confidant');
    $this->deck = 'B';
    $this->number = 93;
    $this->category = ACTIONS_BOOSTER;
    $this->desc = [
      clienttranslate(
        'Place 1 <FOOD> from your supply on each of the next 2, 3, or 4 round spaces. At the start of these rounds, you get the <FOOD> back and your choice of a __Sow__ or __Build Fences__ action.'
      ),
    ];
    $this->players = '1+';
    $this->isArtifexOrBubulcus = true;
  }
  
  public function onBuy($player)
  {
    $max = min(14 - Globals::getTurn(), 4);
    
    $childs = [];
    for ($i = 2; $i <= max($max, 2); $i++) {
      $childs[] = [
        'type' => NODE_SEQ,
        'customDescription' => [
          'log' => clienttranslate('Place ${n} <FOOD> for future rounds'),
          'args' => ['n' => $i],
        ],
        'childs' => [
          $this->payNode([FOOD => $i]),
          $this->futureMeeplesNode([FOODPLUS => 1], $i, true, $this->id),
        ]
      ];
    }

    return [
      'type' => NODE_XOR,
      'childs' => $childs,
    ];
  }
  
  public function getPostReceiveBonus($meeple)
  {
    return [
      'type' => NODE_XOR,
      'optional' => true,
      'childs' => [
        [
          'action' => SOW,
          'source' => $this->name,
        ],
        [
          'action' => FENCING,
          'source' => $this->name,
          'args' => ['costs' => Utils::formatCost([WOOD => 1])],
        ]
      ]
    ];
  }
}
