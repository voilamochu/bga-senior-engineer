<?php
namespace AGR\Cards\B;
use AGR\Managers\Players;

class B163_Pastor extends \AGR\Models\Occupation
{
  public function __construct($row)
  {
    parent::__construct($row);
    $this->id = 'B163_Pastor';
    $this->name = clienttranslate('Pastor');
    $this->deck = 'B';
    $this->number = 163;
    $this->category = BUILDING_RESOURCE_PROVIDER;
    $this->desc = [
      clienttranslate(
        'Once you are the only player to live in a house with only 2 rooms, you immediately get 3 <WOOD>, 2 <CLAY>, 1 <REED>, and 1 <STONE> (only once).'
      ),
    ];
    $this->players = '4+';
  }

  public function onBuy($player)
  {
    return $this->onAfterConstruct($player, []);
  }

  public function isListeningTo($event)
  {
    return $this->isActionEvent($event, 'Construct', null);
  }

  public function onAfterConstruct($player, $event)
  {
    $player = $this->getPlayer();
    if ($this->isFlagged()) {
      return null;
    }

    if ($player->countRooms() > 2) {
      return $this->flagCardNode();
    }

    foreach (Players::getAll() as $othPlayer) {
      if ($player->getId() == $othPlayer->getId()) {
        continue;
      }

      if ($othPlayer->countRooms() == 2) {
        return null;
      }
    }

    return [
      'type' => NODE_SEQ,
      'childs' => [
        $this->gainNode([WOOD => 3, CLAY => 2, REED => 1, STONE => 1]),
        $this->flagCardNode(),
      ],
    ];
  }
}
