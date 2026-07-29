<?php
namespace AGR\Cards\B;

use AGR\Core\Notifications;
use AGR\Managers\Meeples;

class B17_ForestPlow extends \AGR\Models\MinorImprovement
{
  public function __construct($row)
  {
    parent::__construct($row);
    $this->id = 'B17_ForestPlow';
    $this->name = clienttranslate('Forest Plow');
    $this->deck = 'B';
    $this->number = 17;
    $this->category = FARM_PLANNER;
    $this->desc = [
      clienttranslate(
        'Each time after you use a wood accumulation space, you can pay 2 <WOOD> to plow 1 field. Place the paid <WOOD> on the accumulation space (for the next visitor).'
      ),
    ];
    $this->cost = [
      WOOD => 1,
    ];
    $this->isArtifexOrBubulcus = true;

    $this->rulings = [
      clienttranslate('You may take less than 2 <WOOD> from the space and still use this card\'s effect.'),
    ];
  }

  public function isListeningTo($event)
  {
    return $this->isCollectEvent($event, WOOD);
  }

  public function onPlayerAfterCollect($player, $event)
  {
    return [
      'type' => NODE_SEQ,
      'optional' => true,
      'childs' => [
        $this->moveResourcesToSpaceNode([WOOD => 2], $event['actionCardId']),
        [
          'action' => PLOW,
          'cardId' => $this->id,
          'source' => $this->name,
        ],
      ]
    ];
  }
}
