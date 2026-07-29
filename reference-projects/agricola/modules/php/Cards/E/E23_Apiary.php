<?php
namespace AGR\Cards\E;

use AGR\Helpers\UsedText;
use AGR\Helpers\CardRulings;

class E23_Apiary extends \AGR\Models\MinorImprovement
{
  public function __construct($row)
  {
    parent::__construct($row);
    $this->id = 'E23_Apiary';
    $this->name = clienttranslate('Apiary');
    $this->deck = 'E';
    $this->author = 'beso';
    $this->number = 23;
    $this->category = 'ACTION';
    $this->desc = [clienttranslate('At the end of each work phase, you can sow exactly 1 crop on 1 field.')];
    $this->prerequisite = clienttranslate('4 Occupations');
    $this->occupationPrerequisites = ['min' => 4];
    $this->implemented = true;
    $this->rulings = CardRulings::fromKeys([
      'WOOD_STONE_NOT_CROPS',
    ]);
    $this->usedText = UsedText::get('SOWS');
  }
  public function isListeningTo($event)
  {
    return $this->isPlayerEvent($event) && 
	  $event['type'] == 'EndWorkPhase';
  }
  public function onPlayerEndWorkPhase($player, $event)
  {  
    return [
      'countAsUse' => true,
      'action' => SOW,
      'optional' => true,
      'source' => $this->name,
	  'args' => ['max' => 1, 'type' => CROPS, 'cardId' => $this->id],
    ];
  }
}
