<?php
namespace AGR\Cards\B;

use AGR\Helpers\Utils;
use AGR\Helpers\CardRulings;

class B89_Groom extends \AGR\Models\Occupation
{
  public function __construct($row)
  {
    parent::__construct($row);
    $this->id = 'B89_Groom';
    $this->name = clienttranslate('Groom');
    $this->deck = 'B';
    $this->number = 89;
    $this->category = FARM_PLANNER;
    $this->desc = [
      clienttranslate(
        'When you play this card, immediately get 1 <WOOD>. Once you live in a stone house, at the start of each round, you can build exactly 1 stable for 1 <WOOD>.'
      ),
    ];
    $this->players = '1+';

    $this->rulings = CardRulings::fromKeys([
      'SCHOLAR_IMMEDIATE_USE',
    ]);
  }

  public function onBuy($player)
  {
    return $this->gainNode([WOOD => 1]);
  }

  public function isListeningTo($event)
  {
    return $this->isPlayerEvent($event) &&
      $event['type'] == 'StartOfTurn' &&
      $this->getPlayer()->getRoomType() == 'roomStone';
  }

  public function onPlayerStartOfTurn($player, $event)
  {
    return [
      'action' => STABLES,
      'cardId' => $this->id,
      'optional' => true,
      'args' => [
        'max' => 1,
        'costs' => Utils::formatCost([WOOD => 1, 'max' => 1]),
      ],
    ];
  }
}
