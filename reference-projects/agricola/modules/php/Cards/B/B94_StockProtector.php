<?php

namespace AGR\Cards\B;

use AGR\Managers\Actions;
use AGR\Models\Occupation;

class B94_StockProtector extends Occupation
{
  public function __construct($row)
  {
    parent::__construct($row);
    $this->id = 'B94_StockProtector';
    $this->name = clienttranslate('Stock Protector');
    $this->deck = 'B';
    $this->number = 94;
    $this->category = ACTIONS_BOOSTER;
    $this->desc = [
      clienttranslate(
        'Each time before you use the __Fencing__ action space, you get 2 <WOOD>. Immediately after that __Fencing__ action, you can place another person.'
      ),
    ];
    $this->players = '1+';
    $this->isArtifexOrBubulcus = true;
  }

  public function isListeningTo($event)
  {
    return ($this->isActionCardEvent($event, 'Fencing')) ||
      ($this->isActionEvent($event, 'PlaceFarmer') && $event['actionCardType'] == 'Fencing');
  }

  public function onPlayerPlaceFarmer($player, $event)
  {
    return $this->gainNode([WOOD => 2]);
  }

  public function onPlayerAfterPlaceFarmer($player, $event)
  {
    return [
      'countAsUse' => true,
      'type' => NODE_SEQ,
      'optional' => true,
      'childs' => [
        $this->setTurnIdNode(),
        [
          'action' => PLACE_FARMER,
          'args' => [],
          'source' => $this->name,
        ],
      ],
    ];
  }

  public function onPlayerIsDoable($player, &$args)
  {
    if ($args['isDoable']) {
      return;
    }

    if ($args['action'] == FENCING) {
      $args['isDoable'] = Actions::get(FENCING)->isDoable($player, true);
    }
  }
}
