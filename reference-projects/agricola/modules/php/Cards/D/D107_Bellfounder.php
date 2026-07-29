<?php
namespace AGR\Cards\D;
use AGR\Core\Notifications;

class D107_Bellfounder extends \AGR\Models\Occupation
{
  public function __construct($row)
  {
    parent::__construct($row);
    $this->id = 'D107_Bellfounder';
    $this->name = clienttranslate('Bellfounder');
    $this->deck = 'D';
    $this->number = 107;
    $this->category = FOOD_PROVIDER;
    $this->desc = [
      clienttranslate(
        'In the returning home phase of each round, if you have at least 1 <CLAY>, you can use this card to discard all of your <CLAY> and get your choice of 3 <FOOD> or 1 bonus <SCORE>.'
      ),
    ];
    $this->players = '1+';
    $this->extraVp = true;
  }

  public function isListeningTo($event)
  {
    return $this->isPlayerEvent($event) && $event['type'] == 'ReturnHome';
  }

  public function onPlayerReturnHome($player, $event)
  {
    return [
      'type' => NODE_SEQ,
      'pId' => $this->pId,
      'optional' => true,
      'countAsUse' => true,
      'childs' => [
        [
          'action' => SPECIAL_EFFECT,
          'args' => [
            'cardId' => $this->id,
            'method' => 'discardAllClay',
            'args' => [],
          ],
        ],
        [
          'type' => NODE_XOR,
          'childs' => [
            $this->gainNode([FOOD => 3]),
            $this->gainNode([SCORE => 1])
          ]
        ],
      ],
    ];
  }

  public function isDiscardAllClayDoable($player, $ignoreResources = false)
  {
    return $ignoreResources || $player->countReserveResource(CLAY) > 0;
  }

  public function discardAllClay()
  {
    $player = $this->getPlayer();
    $deleted = $player->useResource(CLAY, $player->countReserveResource(CLAY));
    Notifications::payResources($player, $deleted, $this->name, [], []);
  }

  public function getDiscardAllClayDescription()
  {
    return [
      'log' => clienttranslate('Discard all ${resources_desc}'),
      'args' => [
        'resources_desc' => '<CLAY>',
      ],
    ];
  }
}
