<?php
namespace AGR\Cards\E;
use AGR\Managers\Meeples;

class E110_Dentist extends \AGR\Models\Occupation
{
  public function __construct($row)
  {
    parent::__construct($row);
    $this->id = 'E110_Dentist';
    $this->name = clienttranslate('Dentist');
    $this->deck = 'E';
    $this->author = 'chris';
    $this->number = 110;
    $this->category = FOOD;
    $this->desc = [
      clienttranslate(
        'At the start of each harvest, you can place 1 <WOOD> from your supply on this card, irretrievably. In each feeding phase, you get 1 <FOOD> for each <WOOD> on this card.'
      ),
    ];
    $this->players = '1+';
    $this->holder = true;
  }

  public function preFeedingGoodsWanted()
  {
    return [WOOD];
  }

  public function isListeningTo($event)
  {
    return ($this->isPlayerEvent($event) && $event['type'] == 'StartHarvest') ||
      ($this->isPlayerEvent($event) && $event['type'] == 'StartHarvestFeedingPhase');;
  }

  public function onPlayerStartHarvest($player, $event)
  {
    return $this->moveResourcesToSpaceNode([WOOD => 1], $this->id, true);
  }

  public function onPlayerStartHarvestFeedingPhase($player)
  {
    $n = count(Meeples::getResourcesOnCard($this->id, null, WOOD));

    if ($n > 0) {
      return $this->gainNode([FOOD => $n]);
    }
  }
}
