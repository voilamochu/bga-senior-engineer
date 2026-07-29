<?php
namespace AGR\Cards\B;

use AGR\Core\Notifications;
use AGR\Managers\Meeples;

class B28_ForestryStudies extends \AGR\Models\MinorImprovement
{
  public function __construct($row)
  {
    parent::__construct($row);
    $this->id = 'B28_ForestryStudies';
    $this->name = clienttranslate('Forestry Studies');
    $this->deck = 'B';
    $this->number = 28;
    $this->category = ACTIONS_BOOSTER;
    $this->desc = [
      clienttranslate(
        'Each time after you use the __Forest__ accumulation space, you can return 2 <WOOD> to that space to play 1 occupation without paying an occupation costs.'
      ),
    ];
    $this->cost = [
      FOOD => 2,
    ];
    $this->isArtifexOrBubulcus = true;
  }
  
  public function isListeningTo($event)
  {
    return $this->isActionEvent($event, 'PlaceFarmer') && 
	  $event['actionCardType'] == 'Forest';
  }
  
  public function onPlayerAfterPlaceFarmer($player, $event)
  {
    return [
      'type' => NODE_SEQ,
      'optional' => true,
      'childs' => [
        $this->moveResourcesToSpaceNode([WOOD => 2], $event['actionCardId']),
        [
          'action' => OCCUPATION,
          'cardId' => $this->id,
          'args' => [
            'cost' => [],
            'source' => $this->name,
          ],		  
        ],
      ]
    ];
  }
}
