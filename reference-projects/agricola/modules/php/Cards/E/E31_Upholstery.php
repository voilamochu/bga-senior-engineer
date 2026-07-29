<?php
namespace AGR\Cards\E;
use AGR\Managers\Meeples;

class E31_Upholstery extends \AGR\Models\MinorImprovement
{
  public function __construct($row)
  {
    parent::__construct($row);
    $this->id = 'E31_Upholstery';
    $this->name = clienttranslate('Upholstery');
    $this->deck = 'E';
    $this->author = 'cunning';
    $this->number = 31;
    $this->category = 'BONUS_POINTS_-_GET';
    $this->desc = [
      clienttranslate(
        'Each time you build or play an improvement after this one, you can place 1 <REED> on this card, irretrievably, to get 1 bonus <SCORE>, up to the number of rooms in your house.'
      ),
    ];
    $this->extraVp = true;
    $this->holder = true;
  }

  public function isListeningTo($event)
  {
    return $this->isActionEvent($event, 'Improvement') && 
      Meeples::getResourcesOnCard($this->id, null, REED)->count() < count($this->getPlayer()->board()->getRooms());
  }

  public function onPlayerAfterImprovement($player, $event)
  {
    if($event['cardId'] == 'E31_Upholstery') {
      return;
    }

    return [
      'type' => NODE_SEQ,
      'optional' => true,
      'childs' => [
        $this->moveResourcesToSpaceNode([REED => 1], $this->id),
        $this->gainNode([SCORE => 1]),
      ]
    ];
  }
}
