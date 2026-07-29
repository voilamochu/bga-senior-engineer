<?php
namespace AGR\Cards\C;

use AGR\Core\Globals;
use AGR\Helpers\Utils;

class C165_GameCatcher extends \AGR\Models\Occupation
{
  protected $map = [
    1 => 6,
    2 => 6,
    3 => 6,
    4 => 6,
    5 => 5,
    6 => 5,
    7 => 5,
    8 => 4,
    9 => 4,
    10 => 3,
    11 => 3,
    12 => 2,
    13 => 2,
    14 => 1,
  ];

  public function __construct($row)
  {
    parent::__construct($row);
    $this->id = 'C165_GameCatcher';
    $this->name = clienttranslate('Game Catcher');
    $this->deck = 'C';
    $this->number = 165;
    $this->isCorbariusOrDulcinaria = true;
    $this->category = LIVESTOCK_PROVIDER;
    $this->desc = [
      clienttranslate(
        'When you play this card, pay 1 <FOOD> for each remaining harvest to immediately get 1 <CATTLE> and 1 <PIG>.'
      ),
    ];
    $this->players = '4+';
  }

  public function isBuyable($player, $ignoreResources = false, $args = [])
  {
    $turn = Globals::getTurn();
    if ($player->countReserveResource(FOOD) < $this->map[$turn]) {
      return false;
    }

    return parent::isBuyable($player, $ignoreResources, $args);
  }

  public function onBuy($player)
  {
    $turn = Globals::getTurn();

    return [
      'type' => NODE_SEQ,
      'childs' => [
        [
          'action' => PAY,
          'args' => [
            'nb' => 1,
            'costs' => Utils::formatCost([FOOD => $this->map[$turn]]),
            'source' => $this->name,
          ],
        ],
        [
          'action' => GAIN,
          'args' => [CATTLE => 1, PIG => 1],
          'source' => $this->name,
        ],
      ],
    ];
  }
}
