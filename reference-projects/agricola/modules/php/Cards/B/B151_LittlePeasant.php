<?php
namespace AGR\Cards\B;
use AGR\Managers\ActionCards;
use AGR\Core\Globals;

class B151_LittlePeasant extends \AGR\Models\Occupation
{
  public function __construct($row)
  {
    parent::__construct($row);
    $this->id = 'B151_LittlePeasant';
    $this->name = clienttranslate('Little Peasant');
    $this->deck = 'B';
    $this->number = 151;
    $this->category = ACTIONS_BOOSTER;
    $this->desc = [
      clienttranslate(
        'You immediately get 1 <STONE>. As long as you live in a wooden house with exactly 2 rooms, actions spaces—excluding Meeting Place—are not considered occupied for you.'
      ),
    ];
    $this->players = '4+';
    $this->isArtifexOrBubulcus = true;
    $this->bannedStrong3or4p = true;
  }

  public function onBuy($player)
  {
    return $this->gainNode([STONE => 1]);
  }

  public function canIgnoreOccupacy($player){
    return $player->countRooms() == 2 && $player->getRoomType() == 'roomWood';
  }

  public function onPlayerComputeArgsPlaceFarmer($player)
  {
    if (!$this->canIgnoreOccupacy($player)) {
      return;
    }
    $added = [];

    $cards = ActionCards::getVisible($player);
    foreach ($cards as $card) {
      if ($card->getActionCardType() == 'MeetingPlace') {
        continue;
      }

      $added[] = ['actionCardId' => $card->getId(), 'playerConstraint' => 'dummy'];
    }

    return $added;
  }
}
