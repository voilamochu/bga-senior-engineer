<?php
namespace AGR\Cards\C;
use AGR\Managers\Players;

class C64_CornSchnappsDistillery extends \AGR\Models\MinorImprovement
{
  public function __construct($row)
  {
    parent::__construct($row);
    $this->id = 'C64_CornSchnappsDistillery';
    $this->name = clienttranslate('Corn Schnapps Distillery');
    $this->deck = 'C';
    $this->number = 64;
    $this->category = FOOD_PROVIDER;
    $this->desc = [
      clienttranslate(
        'Once per round, you can pay 1 <GRAIN> to place 1 <FOOD> on each of the next 4 round spaces. At the start of these rounds, you get the <FOOD>.'
      ),
    ];
    $this->vp = 1;
    $this->cost = [
      WOOD => 1,
      CLAY => 2,
    ];
    $this->isCorbariusOrDulcinaria = true;
  }
  
  public function isListeningTo($event)
  {
    return ($this->isAnytime($event) && !$this->isFlagged()) || // Use the card
      ($this->isPlayerEvent($event) && $event['type'] == 'StartOfTurn'); // Unuse the card
  }

  public function onPlayerAtAnytime($player, $event)
  {
    return [
      'type' => NODE_SEQ,
      'countAsUse' => true,
      'childs' => [
        $this->payNode([GRAIN => 1]),
        $this->futureMeeplesNode([FOOD => 1], 4),
        $this->flagCardNode(),
      ]
    ];
  }
  
  public function onPlayerStartOfTurn($player, $event)
  {
    return $this->unflagCardNode();
  }
}
