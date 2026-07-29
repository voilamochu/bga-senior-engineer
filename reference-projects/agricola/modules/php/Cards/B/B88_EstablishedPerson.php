<?php
namespace AGR\Cards\B;

use AGR\Helpers\Utils;

class B88_EstablishedPerson extends \AGR\Models\Occupation
{
  public function __construct($row)
  {
    parent::__construct($row);
    $this->id = 'B88_EstablishedPerson';
    $this->name = clienttranslate('Established Person');
    $this->deck = 'B';
    $this->number = 88;
    $this->category = FARM_PLANNER;
    $this->desc = [
      clienttranslate(
        'If your house has exactly 2 rooms, immediately renovate it without paying any building resources. If you do, you can immediately afterward take a __Build Fences__ action.'
      ),
    ];
    $this->players = '1+';
    $this->isArtifexOrBubulcus = true;
  }

  public function onBuy($player)
  {
    if (
      $player->countRooms() != 2 ||
      $player->getRoomType() == 'roomStone' ||
      $player->hasRenoBlockingCards()
    ) {
      return;
    }

    return [
      'type' => NODE_SEQ,
      'childs' => [
        [
          'action' => RENOVATION,
          'pId' => $this->pId,
          'args' => ['costs' => Utils::formatCost([])],
        ],
        [
          'action' => FENCING,
          'pId' => $this->getPlayer()->getId(),
          'optional' => true,
          'source' => $this->name,
          'args' => ['costs' => Utils::formatCost([WOOD => 1])],
        ]
      ]
    ];
  }
}