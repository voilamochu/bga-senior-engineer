<?php
namespace AGR\Cards\D;

use AGR\Core\Globals;
use AGR\Core\Notifications;
use AGR\Managers\Farmers;

class D150_GodlySpouse extends \AGR\Models\Occupation
{
  public function __construct($row)
  {
    parent::__construct($row);
    $this->id = 'D150_GodlySpouse';
    $this->name = clienttranslate('Godly Spouse');
    $this->deck = 'D';
    $this->number = 150;
    $this->category = ACTIONS_BOOSTER;
    $this->desc = [
      clienttranslate(
        'Each time you take a __Family Growth__ action with the second person you place in a round, return the first person you placed home, unless it is on the __Meeting Place__ action space.'
      ),
    ];
    $this->players = '4+';
    $this->isCorbariusOrDulcinaria = true;
  }

  public function isListeningTo($event)
  {
    return ($this->isActionEvent($event, 'WishChildren') && !$this->isFlagged()) ||
      ($this->isPlayerEvent($event) && $event['type'] == 'StartOfTurn');
  }

  public function onPlayerStartOfTurn($player, $event)
  {
    return $this->unflagCardNode();
  }

  public function onPlayerAfterWishChildren($player, $event)
  {
    if ($player->countPlacedFarmers() != 2) {
      return;
    }

    return [
      'countAsUse' => true,
      'type' => NODE_SEQ,
      'optional' => true,
      'childs' => [
        $this->flagCardNode(),
        [
          'action' => SPECIAL_EFFECT,
          'args' => [
            'cardId' => $this->id,
            'method' => 'returnFarmer',
          ],
        ],
      ],
    ];
  }

  public function returnFarmer()
  {
    $player = $this->getPlayer();

    $first = Globals::getPlacedFarmers()[$player->getId()][0];

    $loc = Farmers::getMany($first)->first()['location'];
    if (in_array($loc, ['ActionMeetingPlace', 'ActionMeetingPlaceSolo'])) {
      return;
    }

    Farmers::cleanupJobContractFake();

    $player->returnHomeOne($first);
    Notifications::returnHomeOne($player, Farmers::getMany($first));
  }
}
