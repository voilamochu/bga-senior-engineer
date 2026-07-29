<?php
namespace AGR\Cards\E;

use AGR\Helpers\UsedText;
use AGR\Core\Globals;

class E26_Sundial extends \AGR\Models\MinorImprovement
{
  public function __construct($row)
  {
    parent::__construct($row);
    $this->id = 'E26_Sundial';
    $this->name = clienttranslate('Sundial');
    $this->deck = 'E';
    $this->author = 'oxmond';
    $this->number = 26;
    $this->category = 'ACTION';
    $this->desc = [
      clienttranslate(
        'At the end of the work phases of rounds 7 and 9, you can take a __Sow__ action without placing a person.'
      ),
    ];
    $this->cost = [
      WOOD => 1,
    ];
    $this->usedText = UsedText::get('SOWS');
  }

  public function isListeningTo($event)
  {
    return $this->isPlayerEvent($event) && 
	  $event['type'] == 'EndWorkPhase' &&
      ((Globals::getTurn()==7)||(Globals::getTurn()==9));
  }

  public function onPlayerEndWorkPhase($player, $event)
  {  
    return [
      'countAsUse' => true,
      'action' => SOW,
      'optional' => true,
      'source' => $this->name,
    ];
  }
}
