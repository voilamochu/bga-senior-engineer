<?php
namespace AGR\Cards\C;

use AGR\Managers\Meeples;
use AGR\Core\Notifications;
use AGR\Helpers\Utils;

class C87_Mason extends \AGR\Models\Occupation
{
  public function __construct($row)
  {
    parent::__construct($row);
    $this->id = 'C87_Mason';
    $this->name = clienttranslate('Mason');
    $this->deck = 'C';
    $this->number = 87;
    $this->category = FARM_PLANNER;
    $this->desc = [
      clienttranslate(
        'Place a stone room on this card. Once you have a stone house with at least 4 rooms, at any time, you can add that room without paying any building resources.'
      ),
    ];
    $this->players = '1+';
    $this->holder = true;
  }

  public function onBuy($player)
  {
    $created = Meeples::createResourceInLocation('roomStone', $this->id, $player->getId(), null, null, 1);
    Notifications::accumulate(Meeples::getMany($created), true);
  }

  public function isListeningTo($event)
  {
    $player = $this->getPlayer();
    return $this->isAnytime($event) &&
      Meeples::getResourcesOnCard($this->id)->count() > 0 &&
      $player->getRoomType() == 'roomStone' &&
      $player->countRooms() >= 4 &&
      !$this->isFlagged();
  }

  public function onPlayerAtAnytime($player, $event)
  {
    return [
      'type' => NODE_SEQ,
      'childs' => [
        $this->flagCardNode(),
        [
          'action' => SPECIAL_EFFECT,
          'args' => [
            'cardId' => $this->id,
            'method' => 'removeRoom',
            'args' => [],
          ],
        ],
        [
          'action' => CONSTRUCT,
          'args' => [
            'costs' => Utils::formatCost(['max' => 1]),
            'max' => 1,
            'source' => $this->name,
            'trueAction' => false
          ],
          'source' => $this->name,
        ],
      ],
    ];
  }

  public function removeRoom()
  {
    $room = Meeples::getResourcesOnCard($this->id)->first();
    Meeples::DB()->delete($room['id']);
    Notifications::silentDestroy([$room]);
  }
}
