<?php
namespace AGR\Cards\B;
use AGR\Core\Globals;

class B70_NewPurchase extends \AGR\Models\MinorImprovement
{
  public function __construct($row)
  {
    parent::__construct($row);
    $this->id = 'B70_NewPurchase';
    $this->name = clienttranslate('New Purchase');
    $this->deck = 'B';
    $this->number = 70;
    $this->category = CROP_PROVIDER;
    $this->desc = [
      clienttranslate(
        'Before the start of each round that ends with a harvest, you can buy one of each of the following crops: 2 <FOOD> <ARROW> 1 <GRAIN>; 4 <FOOD> <ARROW> 1 <VEGETABLE>'
      ),
    ];
    $this->isArtifexOrBubulcus = true;
  }
  
  public function isListeningTo($event)
  {
    return $this->isPlayerEvent($event) && $event['type'] == 'BeforeStartOfTurn';
  }
  
  public function onPlayerBeforeStartOfTurn($player, $event)
  {
    $turn = Globals::getTurn()+1;
    if (!in_array($turn, [4, 7, 9, 11, 13, 14])) {
      return null;
    }
    
    return [
      'type' => NODE_OR,
      'optional' => true,
      'childs' => [
        $this->payGainNode([FOOD => 2], [GRAIN => 1], null, false),
        $this->payGainNode([FOOD => 4], [VEGETABLE => 1], null, false),
      ]
    ];
  }
}
