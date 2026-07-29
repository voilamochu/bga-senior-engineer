<?php
namespace AGR\Cards\D;
use AGR\Core\Notifications;
use AGR\Managers\Meeples;

//use AGR\Helpers\Utils;

class D101_SugarBaker extends \AGR\Models\Occupation
{
  public function __construct($row)
  {
    parent::__construct($row);
    $this->id = 'D101_SugarBaker';
    $this->name = clienttranslate('Sugar Baker');
    $this->deck = 'D';
    $this->number = 101;
    $this->category = POINTS_PROVIDER;
    $this->desc = [
      clienttranslate(
        'Each time after you use the __Grain Utilization__ action space, you can buy 1 bonus <SCORE> for 1 <FOOD>. Place the <FOOD> on the action space (for the next visitor).'
      ),
    ];
    $this->players = '1+';
    $this->extraVp = true;
    $this->bannedWeak1or2p = true;
    $this->bannedWeak3or4p = true;
  }
  
  public function isListeningTo($event)
  {
    return $this->isActionEvent($event, 'PlaceFarmer') && $event['actionCardType'] == 'GrainUtilization';
  }

  public function onPlayerAfterPlaceFarmer($player, $event)
  {
    return [
      'type' => NODE_SEQ,
      'optional' => true,
      'childs' => [
        $this->payGainNode([FOOD => 1], [SCORE => 1], null, false),
        [
          'action' => SPECIAL_EFFECT,
          'args' => [
            'cardId' => $this->id,
            'method' => 'placeFood',
            'args' => [$event['actionCardId']],
          ],
        ],
      ],
    ];
  }

  public function placeFood($actionCardId)
  {
    $resourceIds = Meeples::createResourceOnCard(FOOD, $actionCardId, 1);
    Notifications::accumulate(Meeples::getMany($resourceIds), true);
  }
}
