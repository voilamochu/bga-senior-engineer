<?php

namespace AGR\Cards\A;

use AGR\Helpers\UsedText;
use AGR\Models\MinorImprovement;
use AGR\Helpers\Utils;

class A23_StoneCompany extends MinorImprovement
{
  public function __construct($row)
  {
    parent::__construct($row);
    $this->id = 'A23_StoneCompany';
    $this->name = clienttranslate('Stone Company');
    $this->deck = 'A';
    $this->number = 23;
    $this->category = ACTIONS_BOOSTER;
    $this->desc = [
      clienttranslate(
        'Immediately after each time you use a __Quarry__ accumulation space, you get a __Major or Minor Improvement__ action during which you must spend at least 1 <STONE>.'
      ),
    ];
    $this->vp = 1;
    $this->cost = [
      CLAY => '2',
      REED => '1',
    ];
    $this->isArtifexOrBubulcus = true;
    $this->usedText = UsedText::get('IMPROVEMENTS_BUILT');
  }

  public function isListeningTo($event)
  {
    return $this->isCollectEvent($event, STONE) &&
      ($event['actionCardId'] == 'ActionEasternQuarry' || $event['actionCardId'] == 'ActionWesternQuarry');
  }

  public function onPlayerAfterCollect($player, $args)
  {
    return Utils::wrapOptional([
      'countAsUse' => true,
      'action' => IMPROVEMENT,
      'source' => $this->name,
      'args' => [
        'types' => [MINOR, MAJOR],
        'actionType' => 'MajorOrMinor',
        'purchaseCondition' => $this->id
      ],
    ]);
  }
}
