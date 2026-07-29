<?php
namespace AGR\Cards\E;

use AGR\Core\Globals;
use AGR\Helpers\Utils;

class E87_MasterRenovator extends \AGR\Models\Occupation
{
  public function __construct($row)
  {
    parent::__construct($row);
    $this->id = 'E87_MasterRenovator';
    $this->name = clienttranslate('Master Renovator');
    $this->deck = 'E';
    $this->author = 'really_unknow4';
    $this->number = 87;
    $this->category = 'FARMYARD_-_HOUSE_BUILDING_OR_RENOVATION';
    $this->desc = [
      clienttranslate(
        'At the end of the work phases of rounds 7 and 9, you can take a __Renovation__ action without placing a person and pay 1 building resource of your choice less.'
      ),
    ];
    $this->players = '1+';
  }

  public function isListeningTo($event)
  {
    return $this->isPlayerEvent($event) &&
      $event['type'] == 'EndWorkPhase' &&
      (Globals::getTurn() == 7 || Globals::getTurn() == 9);
  }

  public function onPlayerEndWorkPhase($player, $event)
  {
    if ($player->getRoomType() != 'roomStone') {
      return [
        'countAsUse' => true,
        'type' => NODE_SEQ,
'optional' => true,
        'childs' => [
          $this->flagCardNode(),
          [
            'action' => RENOVATION,
            'pId' => $this->pId,
            'optional' => true,
          ],
          $this->unflagCardNode(),
        ],
      ];
    }
  }

  public function onPlayerComputeCostsRenovation($player, &$args)
  {
    if (!$this->isFlagged()) {
      return;
    }
    Utils::addBonusChoices($args['costs'], [
      [WOOD => -1],
      [CLAY => -1],
      [STONE => -1],
      [REED => -1],
    ], $this->id);
  }
}
