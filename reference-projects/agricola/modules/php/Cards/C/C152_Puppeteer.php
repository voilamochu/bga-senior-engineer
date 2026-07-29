<?php
namespace AGR\Cards\C;

use AGR\Helpers\Utils;

class C152_Puppeteer extends \AGR\Models\Occupation
{
  public function __construct($row)
  {
    parent::__construct($row);
    $this->id = 'C152_Puppeteer';
    $this->name = clienttranslate('Puppeteer');
    $this->deck = 'C';
    $this->number = 152;
    $this->isCorbariusOrDulcinaria = true;
    $this->category = ACTIONS_BOOSTER;
    $this->desc = [
      clienttranslate(
        'Each time another player uses the __Traveling Players__ accumulation space, you can pay them 1 <FOOD> to immediately play an occupation without paying an occupation cost.'
      ),
    ];
    $this->players = '4+';
  }

  public function isListeningTo($event)
  {
    return $this->isActionEvent($event, 'PlaceFarmer', 'opponent') && $event['actionCardType'] == 'TravelingPlayers';
  }

  public function onOpponentAfterPlaceFarmer($player, $args)
  {
    return [
      'type' => NODE_SEQ,
      'pId' => $this->pId,
      'childs' => [
        [
          'type' => NODE_SEQ,
          'optional' => true,
          'forceConfirmation' => true,
          'pId' => $this->pId,
          'childs' => [
            [
              'action' => PAY,
              'args' => [
                'nb' => 1,
                'costs' => Utils::formatCost([FOOD => 1]),
                'source' => $this->name,
                'to' => $player->getId(),
              ],
            ],
            [
              'action' => OCCUPATION,
              'cardId' => $this->id,
              'pId' => $this->pId,
              'args' => [
                'max' => 1,
                'cost' => [],
              ],
            ],
          ],
        ],
      ],
    ];
  }
}
