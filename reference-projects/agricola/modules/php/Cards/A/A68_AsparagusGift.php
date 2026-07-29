<?php
namespace AGR\Cards\A;
use AGR\Core\Globals;

class A68_AsparagusGift extends \AGR\Models\MinorImprovement
{
  public function __construct($row)
  {
    parent::__construct($row);
    $this->id = 'A68_AsparagusGift';
    $this->name = clienttranslate('Asparagus Gift');
    $this->deck = 'A';
    $this->number = 68;
    $this->category = CROP_PROVIDER;
    $this->desc = [
      clienttranslate(
        'Each time you build a number of fences equal to or greater than the current round, you immediately get 1 <VEGETABLE>.'
      ),
    ];
    $this->prerequisite = clienttranslate('1 Unplanted Field');
    $this->isArtifexOrBubulcus = true;
  }
  
  public function isBuyable($player, $ignoreResources = false, $args = [])
  {
    $emptyFields = $player->board()->countEmptyLogicalFields();
    if ($emptyFields == 0) {
      return false;
    }
	
    return parent::isBuyable($player, $ignoreResources, $args);	
  }
  
  public function isListeningTo($event)
  {
    return $this->isActionEvent($event, 'Fencing') && count($event['fences']) >= Globals::getTurn();
  }

  public function onPlayerAfterFencing($player, $event)
  {
    return $this->gainNode([VEGETABLE => 1]);
  }  
}
