<?php
namespace AGR\Cards\C;

class C103_GreenGrocer extends \AGR\Models\Occupation
{
  public function __construct($row)
  {
    parent::__construct($row);
    $this->id = 'C103_GreenGrocer';
    $this->name = clienttranslate('Green Grocer');
    $this->deck = 'C';
    $this->number = 103;
    $this->category = GOODS_PROVIDER;
    $this->desc = [
      clienttranslate(
        'At the start of each round, you can make exactly one of the following exchanges: 1 <CATTLE> <ARROW> 1 <VEGETABLE>; 1 <VEGETABLE> <ARROW> 1 <CATTLE>; 2 <SHEEP> <ARROW> 1 <VEGETABLE>; 1 <VEGETABLE> <ARROW> 2 <SHEEP>; 2 <FOOD> <ARROW> 1 <GRAIN>; 1 <GRAIN> <ARROW> 2 <FOOD>'
      ),
    ];
    $this->players = '1+';
    $this->isCorbariusOrDulcinaria = true;
  }
  
  public function isListeningTo($event)
  {
    return $this->isPlayerEvent($event) &&
      $event['type'] == 'StartOfTurn';
  }

  public function onPlayerStartOfTurn($player, $event)
  {
    return [
      'type' => NODE_XOR,
      'optional' => true,
      'countAsUse' => true,
      'doableRequiresChild' => true,
      'customDescription' => 'Exchange goods',
      'childs' => [
        $this->payGainNode([CATTLE => 1],[VEGETABLE => 1], null, false),
        $this->payGainNode([VEGETABLE => 1],[CATTLE => 1], null, false),
        $this->payGainNode([SHEEP => 2],[VEGETABLE => 1], null, false),
        $this->payGainNode([VEGETABLE => 1],[SHEEP => 2], null, false),
        $this->payGainNode([FOOD => 2],[GRAIN => 1], null, false),
        $this->payGainNode([GRAIN => 1],[FOOD => 2], null, false),        
      ]
    ];
  }
}
