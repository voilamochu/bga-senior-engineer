<?php
namespace AGR\Cards\A;
use AGR\Managers\Meeples;
use AGR\Managers\Players;

class A140_ShovelBearer extends \AGR\Models\Occupation
{
  public function __construct($row)
  {
    parent::__construct($row);
    $this->id = 'A140_ShovelBearer';
    $this->name = clienttranslate('Shovel Bearer');
    $this->deck = 'A';
    $this->number = 140;
    $this->category = FOOD_PROVIDER;
    $this->desc = [
      clienttranslate(
        'Each time you use the __Clay Pit__ or __Hollow__ accumulation space, you also get a number of <FOOD> equal to the amount of <CLAY> on the respective other accumulation space.'
      ),
    ];
    $this->players = '3+';
    $this->isArtifexOrBubulcus = true;
  }
  
  public function isListeningTo($event)
  {
    return $this->isActionCardEvent($event, 'ClayPit') || 
      $this->isActionCardEvent($event, 'Hollow');
  }

  public function onPlayerPlaceFarmer($player, $event)
  {
    $other = $event['actionCardType'] == 'ClayPit' ? 'ActionHollow' : 'ActionClayPit';
    if (Players::count() >= 4 && $other == 'ActionHollow') {
      $other = 'ActionHollow4';
    }

    $clay = Meeples::getResourcesOnCard($other, null, CLAY)->count();
    
    if ($clay > 0) {
      return $this->gainNode([FOOD => $clay]);
    }
  }
}
