<?php
namespace AGR\Cards\D;

use AGR\Helpers\Utils;

class D90_PlowMaker extends \AGR\Models\Occupation
{
  public function __construct($row)
  {
    parent::__construct($row);
    $this->id = 'D90_PlowMaker';
    $this->name = clienttranslate('Plow Maker');
    $this->deck = 'D';
    $this->number = 90;
    $this->category = FARM_PLANNER;
    $this->desc = [
      clienttranslate(
        'Each time you use the __Farmland__ or __Cultivation__ action space, you can pay 1 <FOOD> to plow 1 additional field.'
      ),
    ];
    $this->players = '1+';
  }

  public function isListeningTo($event)
  {
    return $this->isActionCardEvent($event, 'Farmland') || $this->isActionCardEvent($event, 'Cultivation');
  }

  public function onPlayerPlaceFarmer($player, $event)
  {
    return [
      'type' => NODE_SEQ,
      'optional' => true,
      'childs' => [
        [
          'action' => PAY,
          'args' => [
            'nb' => 1,
            'costs' => Utils::formatCost([FOOD => 1]),
            'source' => clienttranslate('Plow Maker\'s effect'),
          ],
        ],
        [
          'action' => PLOW,
          'args' => ['source' => $this->name, 'cardId' => $this->id],
        ],
      ],
    ];
  }
}
