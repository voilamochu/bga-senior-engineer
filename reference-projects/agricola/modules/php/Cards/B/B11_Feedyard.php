<?php
namespace AGR\Cards\B;

class B11_Feedyard extends \AGR\Models\MinorImprovement
{
  public function __construct($row)
  {
    parent::__construct($row);
    $this->id = 'B11_Feedyard';
    $this->name = clienttranslate('Feedyard');
    $this->deck = 'B';
    $this->number = 11;
    $this->category = FARM_PLANNER;
    $this->desc = [
      clienttranslate(
        'This card can hold 1 animal for each pasture you have, even different types. After the breeding phase of each harvest, you get 1 <FOOD> for each unused spot on this card.'
      ),
    ];
    $this->vp = 1;
    $this->cost = [
      CLAY => 1,
      GRAIN => 1,
    ];
    $this->animalHolder = true;    
    $this->isArtifexOrBubulcus = true;
  }
  
  public function onPlayerComputeDropZones($player, &$args)
  {
    $pastures = count($player->board()->getPastures(true));
    
    if ($pastures > 0) {
      $args['zones'][] = [
        'type' => 'card',
        'card_id' => $this->id,
        'capacity' => $pastures,
        'locations' => [['type' => 'card', 'card_id' => $this->id]],
      ];
    }
  }
  
  public function getInvalidAnimals($zone, $raiseException)
  {
    $counter = 0;
    $player = $this->getPlayer();
    $pastures = count($player->board()->getPastures(true));
    $animals = [];

    foreach ($zone['meeples'] as $meeple) {
      $counter++;

      if ($counter > $pastures) {
        if ($raiseException) {
          throw new \feException('Too many animals for B11. Should not happen');
        }

        $animals[] = $meeple;
      }
    }

    return $animals;
  }  
  
  public function isListeningTo($event)
  {
    return $this->isPlayerEvent($event) && $event['type'] == 'EndHarvest';
  }

  public function onPlayerEndHarvest($player)
  {
    $zones = $player->board()->getAnimalsDropZonesWithAnimals();
    
    foreach ($zones as &$zone) {
      if (isset($zone['card_id']) && $zone['card_id'] == $this->id) {          
        $n = $zone['capacity'] - $zone['animals'];
        
        if ($n > 0) {
          return $this->gainNode([FOOD => $n]);            
        }
      }
    }
  }
}
