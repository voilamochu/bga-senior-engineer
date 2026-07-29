<?php
namespace AGR\Cards\D;
use AGR\Helpers\Utils;

class D87_MasterBuilder extends \AGR\Models\Occupation
{
  public function __construct($row)
  {
    parent::__construct($row);
    $this->id = 'D87_MasterBuilder';
    $this->name = clienttranslate('Master Builder');
    $this->deck = 'D';
    $this->number = 87;
    $this->category = FARM_PLANNER;
    $this->desc = [
      clienttranslate(
        'Once your house has at least 5 rooms, at any time, but only once this game, you can add another room at no cost.'
      ),
    ];
    $this->players = '1+';
  }

  public function onBuy($player)
  {
    $this->setExtraDatas('used', 0);
  }

  public function isListeningTo($event)
  {
    $player = $this->getPlayer();
    return $this->isAnytime($event) && !$this->isFlagged() && $player->countRooms() >= 5;
  }

  public function onPlayerAtAnytime($player, $event)
  {
    return [
      'type' => NODE_SEQ,
      'childs' => [
        $this->flagCardNode(),
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
}
