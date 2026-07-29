<?php
namespace AGR\Cards\E;

use AGR\Managers\Meeples;

class E158_StoneCustodian extends \AGR\Models\Occupation
{
  public function __construct($row)
  {
    parent::__construct($row);
    $this->id = 'E158_StoneCustodian';
    $this->name = clienttranslate('Stone Custodian');
    $this->deck = 'E';
    $this->author = 'agentricola';
    $this->number = 158;
    $this->category = FOOD;
    $this->desc = [
      clienttranslate(
        'At the end of each work phase, you get 1 <FOOD> for each stone accumulation space with stone on it.'
      ),
    ];
    $this->players = '4+';
    $this->implemented = true;
  }
  public function isListeningTo($event)
  {
    return $this->isPlayerEvent($event) && 
	  $event['type'] == 'EndWorkPhase';
  }
  public function onPlayerEndWorkPhase($player, $event)
  { 
    $foods = 0;
    if (Meeples::getResourcesOnCard ('ActionEasternQuarry', null, STONE)->count()>0)
      $foods++;
    if (Meeples::getResourcesOnCard ('ActionWesternQuarry', null, STONE)->count()>0)
      $foods++;
    if ($foods>0)
      return $this->gainNode([FOOD => $foods]); 
  }
}
