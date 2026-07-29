<?php

namespace AGR\Cards\A;

use AGR\Helpers\UsedText;
use AGR\Models\Occupation;
use AGR\Helpers\Utils;

class A95_Angler extends Occupation
{
  public function __construct($row)
  {
    parent::__construct($row);
    $this->id = 'A95_Angler';
    $this->name = clienttranslate('Angler');
    $this->deck = 'A';
    $this->number = 95;
    $this->category = ACTIONS_BOOSTER;
    $this->desc = [
      clienttranslate(
        'Each time after you use the __Fishing__ Accumulation space while there are at most 2 <FOOD> on that space, you get a __Major or Minor Improvement__ action.'
      ),
    ];
    $this->players = '1+';
    $this->isArtifexOrBubulcus = true;
    $this->usedText = UsedText::get('IMPROVEMENTS_BUILT');
  }

  public function isListeningTo($event)
  {
    return $this->isCollectEvent($event, FOOD) &&
      $event['actionCardId'] == 'ActionFishing' &&
      count($event['meeples']) <= 2;
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
      ],
    ]);
  }
}
