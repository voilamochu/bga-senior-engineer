<?php
namespace AGR\Cards\B;
use AGR\Managers\ActionCards;
use AGR\Managers\Players;

class B63_Tasting extends \AGR\Models\MinorImprovement
{
  public function __construct($row)
  {
    parent::__construct($row);
    $this->id = 'B63_Tasting';
    $this->name = clienttranslate('Tasting');
    $this->deck = 'B';
    $this->number = 63;
    $this->category = FOOD_PROVIDER;
    $this->desc = [
      clienttranslate(
        'Each time you use a __Lessons__ action space, before paying the occupation cost, you can exchange 1 <GRAIN> for 4 <FOOD>.'
      ),
    ];
    $this->vp = 1;
    $this->cost = [
      WOOD => 2,
    ];
    $this->isArtifexOrBubulcus = true;
  }
  
  public function isListeningTo($event)
  {
    return $this->isActionCardEvent($event, 'Lessons');
  }

  public function onPlayerPlaceFarmer($player, $event) 
  {
    return $this->payGainNode([GRAIN => 1], [FOOD => 4]);
  }

  public function onPlayerComputeArgsPlaceFarmer($player)
  {
    $added = [];
    $lessons = ['ActionLessons'];

    if (Players::count() == 3) { $lessons[] = 'ActionLessons3'; }
    elseif (Players::count() >= 4) { $lessons[] = 'ActionLessons4'; }

    foreach ($lessons as $cardId) {
      $added[] = ['actionCardId' => $cardId, 'ignoreResources' => true];
    }
    
    return $added;
  }
}
