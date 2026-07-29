<?php
namespace AGR\Cards\C;
use AGR\Core\Notifications;
use AGR\Managers\Meeples;

class C102_TreeGuard extends \AGR\Models\Occupation
{
  public function __construct($row)
  {
    parent::__construct($row);
    $this->id = 'C102_TreeGuard';
    $this->name = clienttranslate('Tree Guard');
    $this->deck = 'C';
    $this->number = 102;
    $this->category = GOODS_PROVIDER;
    $this->desc = [
      clienttranslate(
        'Each time after you use a wood accumulation space, you can place 4 <WOOD> from your supply on that space to get 2 <STONE>, 1 <CLAY>, 1 <REED>, and 1 <GRAIN>.'
      ),
    ];
    $this->players = '1+';
    $this->isCorbariusOrDulcinaria = true;
    $this->bannedStrong1or2p = true;
    $this->bannedStrong3or4p = true;
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
      'countAsUse' => true,
      'childs' => [
        $this->moveResourcesToSpaceNode([WOOD => 4], $event['actionCardId']),
        $this->gainNode([STONE => 2, CLAY => 1, REED => 1, GRAIN => 1]),
      ]
    ];
  }
}
