<?php
namespace AGR\Cards\B;
use AGR\Core\Globals;

class B107_Manservant extends \AGR\Models\Occupation
{
  public function __construct($row)
  {
    parent::__construct($row);
    $this->id = 'B107_Manservant';
    $this->name = clienttranslate('Manservant');
    $this->deck = 'B';
    $this->number = 107;
    $this->category = FOOD_PROVIDER;
    $this->desc = [
      clienttranslate(
        'Once you live in a stone house, place 3 <FOOD> on each remaining round space. At the start of these rounds, you get the <FOOD>.'
      ),
    ];
    $this->players = '1+';
  }

  public function onBuy($player)
  {
    return $this->onPlayerAfterRenovation($player, []);
  }

  public function isListeningTo($event)
  {
    return $this->isActionEvent($event, 'Renovation') && $event['newRoomType'] == 'roomStone';
  }

  public function onPlayerAfterRenovation($player, $event)
  {
    if ($this->getPlayer()->getRoomType() != 'roomStone') {
      return null;
    }

    return $this->futureMeeplesNode([FOOD => 3], 14);
  }
}
