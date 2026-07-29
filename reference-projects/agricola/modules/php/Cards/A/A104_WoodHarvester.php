<?php
namespace AGR\Cards\A;
use AGR\Managers\ActionCards;
use AGR\Managers\Meeples;

class A104_WoodHarvester extends \AGR\Models\Occupation
{
  public function __construct($row)
  {
    parent::__construct($row);
    $this->id = 'A104_WoodHarvester';
    $this->name = clienttranslate('Wood Harvester');
    $this->deck = 'A';
    $this->number = 104;
    $this->category = GOODS_PROVIDER;
    $this->desc = [
      clienttranslate(
        'In the field phase of each harvest, you get 1 <WOOD>/1 <FOOD> for each wood accumulation space with exactly 2 <WOOD>/at least 3 <WOOD>.'
      ),
    ];
    $this->players = '1+';
    $this->isArtifexOrBubulcus = true;
  }
  
  public function isListeningTo($event)
  {
    return $this->isPlayerEvent($event) &&
      $event['type'] == 'HarvestFieldPhase';
  }
  
  public function onPlayerHarvestFieldPhase($player, $event)
  {
    $spaces = ActionCards::getAccumulationSpaces(WOOD);
    $gain = [];

    foreach ($spaces as $space) {
      $wood = Meeples::getResourcesOnCard($space->id, null, WOOD)->count();

      if ($wood == 2) {
        $gain[WOOD] = ($gain[WOOD] ?? 0) + 1;
      }
      elseif ($wood >= 3) {
        $gain[FOOD] = ($gain[FOOD] ?? 0) + 1;
      }
    }
    
    if ($gain != []) {
      return $this->gainNode($gain);
    }
  }
}
