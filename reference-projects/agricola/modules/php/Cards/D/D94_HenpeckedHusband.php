<?php
namespace AGR\Cards\D;

use AGR\Helpers\UsedText;
use AGR\Core\Globals;
use AGR\Core\Notifications;
use AGR\Managers\Farmers;

class D94_HenpeckedHusband extends \AGR\Models\Occupation
{
  public function __construct($row)
  {
    parent::__construct($row);
    $this->id = 'D94_HenpeckedHusband';
    $this->name = clienttranslate('Henpecked Husband');
    $this->deck = 'D';
    $this->number = 94;
    $this->category = ACTIONS_BOOSTER;
    $this->desc = [
      clienttranslate(
        'Each time you take a __Build Rooms__ action with the second person you place, return the first person you placed home, unless it is on the __Meeting Place__ action space.'
      ),
    ];
    $this->players = '1+';
    $this->isCorbariusOrDulcinaria = true;
    $this->usedText = UsedText::get('FARMERS_RETURNED_HOME');
  }

  public function isListeningTo($event)
  {
    return $this->isActionEvent($event, 'Construct') && $event['trueAction'];
  }

  public function onPlayerAfterConstruct($player, $event)
  {
    if ($player->countPlacedFarmers() != 2) {
      return;
    }

    return [
      'countAsUse' => true,
      'action' => SPECIAL_EFFECT,
      'args' => [
        'cardId' => $this->id,
        'method' => 'returnFarmer',
      ],
    ];
  }

  public function returnFarmer()
  {
    $player = $this->getPlayer();

    $first = Globals::getPlacedFarmers()[$player->getId()][0];

    if (in_array(Farmers::getMany($first)->first()['location'], ['ActionMeetingPlace', 'ActionMeetingPlaceSolo'])) {
      return;
    }

    Farmers::cleanupJobContractFake();

    $player->returnHomeOne($first);
    Notifications::returnHomeOne($player, Farmers::getMany($first));
  }
}
