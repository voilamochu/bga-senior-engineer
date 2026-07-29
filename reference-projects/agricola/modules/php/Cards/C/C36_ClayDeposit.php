<?php
namespace AGR\Cards\C;
use AGR\Core\Notifications;
use AGR\Managers\Meeples;

class C36_ClayDeposit extends \AGR\Models\MinorImprovement
{
  public function __construct($row)
  {
    parent::__construct($row);
    $this->id = 'C36_ClayDeposit';
    $this->name = clienttranslate('Clay Deposit');
    $this->deck = 'C';
    $this->number = 36;
    $this->category = POINTS_PROVIDER;
    $this->desc = [
      clienttranslate(
        'Immediately after each time you use a clay accumulation space, you can exchange 1 <CLAY> for 1 bonus <SCORE>. If you do, place the <CLAY> on the accumulation space.'
      ),
    ];
    $this->cost = [
      FOOD => 2,
    ];
    $this->extraVp = true;
    $this->prerequisite = clienttranslate('1 Occupation');
    $this->occupationPrerequisites = ['min' => 1];
    $this->isCorbariusOrDulcinaria = true;
  }
  
  public function isListeningTo($event)
  {
    return $this->isCollectEvent($event, CLAY);
  }

  public function onPlayerAfterCollect($player, $event)
  {
    return [
      'type' => NODE_SEQ,
      'optional' => true,
      'childs' => [
        $this->moveResourcesToSpaceNode([CLAY => 1], $event['actionCardId']),
        $this->gainNode([SCORE => 1]),
      ],
    ];
  }
}
