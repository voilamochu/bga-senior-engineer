<?php
namespace AGR\Cards\B;
use AGR\Core\Globals;
use AGR\Helpers\Utils;

class B112_Silokeeper extends \AGR\Models\Occupation
{
  protected $map = [
    1 => -1,
    2 => -1,
    3 => -1,
    4 => -1,
    5 => 4,
    6 => 4,
    7 => 4,
    8 => 7,
    9 => 7,
    10 => 9,
    11 => 9,
    12 => 11,
    13 => 11,
    14 => 13,
  ];	
	
  public function __construct($row)
  {
    parent::__construct($row);
    $this->id = 'B112_Silokeeper';
    $this->name = clienttranslate('Silokeeper');
    $this->deck = 'B';
    $this->number = 112;
    $this->category = CROP_PROVIDER;
    $this->desc = [
      clienttranslate(
        'Each time you use the action space card that has been revealed right before the most recent harvest, you also get 1 <GRAIN>.'
      ),
    ];
    $this->players = '1+';
    $this->isArtifexOrBubulcus = true;
  }
  
  public function isListeningTo($event)
  {
    $turn = Globals::getTurn();
    $turnCondition = false;
	
    $cardId = $event['actionCardId'] ?? null;
    if (!is_null($cardId)) {
      if (Utils::getActionCard($cardId)->getTurn() == $this->map[$turn]) {
        $type = Utils::getActionCard($cardId)->getActionCardType();          
        $turnCondition = $this->isActionCardEvent($event, $type);
      }
    }
	
    return $turnCondition;
  }
  
  public function onPlayerPlaceFarmer($player, $args)
  {
    return $this->gainNode([GRAIN => 1]);
  }  
}
