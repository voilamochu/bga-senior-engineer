<?php
namespace AGR\Cards\B;

class B144_Collier extends \AGR\Models\Occupation
{
  public function __construct($row)
  {
    parent::__construct($row);
    $this->id = 'B144_Collier';
    $this->name = clienttranslate('Collier');
    $this->deck = 'B';
    $this->number = 144;
    $this->category = BUILDING_RESOURCE_PROVIDER;
    $this->desc = [
      clienttranslate(
        'Each time after you use the __Clay Pit__ or __Hollow__ accumulation space, you get 1 <WOOD>. On __Clay Pit__ you also get 1 additional <REED>.'
      ),
    ];
    $this->players = '3+';
    $this->isArtifexOrBubulcus = true;
  }
  
  public function isListeningTo($event)
  {
    if (!$this->isActionEvent($event, 'PlaceFarmer')) {
      return false;
    }

    $cardId = $event['actionCardId'] ?? null;
    return $event['actionCardType'] == 'ClayPit' ||
      $event['actionCardType'] == 'Hollow';
  }

  public function onPlayerAfterPlaceFarmer($player, $event)
  {
    if ($event['actionCardType'] == 'ClayPit') {
      return $this->gainNode([WOOD => 1, REED => 1]);
    } else {
      return $this->gainNode([WOOD => 1]);
    }
  }  
}
