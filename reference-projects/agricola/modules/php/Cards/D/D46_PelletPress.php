<?php
namespace AGR\Cards\D;
use AGR\Managers\Players;

class D46_PelletPress extends \AGR\Models\MinorImprovement
{
  public function __construct($row)
  {
    parent::__construct($row);
    $this->id = 'D46_PelletPress';
    $this->name = clienttranslate('Pellet Press');
    $this->deck = 'D';
    $this->number = 46;
    $this->category = FOOD_PROVIDER;
    $this->desc = [
      clienttranslate(
        'Once per round, you can pay 1 <REED>. If you do, place 1 <FOOD> on each of the next 4 round spaces. At the start of these rounds, you get the <FOOD>.'
      ),
    ];
    $this->cost = [
      CLAY => 2,
    ];
    $this->prerequisite = clienttranslate('2 Occupations');
    $this->occupationPrerequisites = ['min' => 2];    
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
	    $this->payNode([REED => 1]),
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
