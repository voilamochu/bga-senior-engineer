<?php
namespace AGR\Cards\Actions;
use AGR\Managers\Farmers;
use AGR\Helpers\Utils;

class ActionAnimalMarketAdd extends \AGR\Models\ActionCard
{
  public function __construct($row)
  {
    parent::__construct($row);
    $this->id = 'ActionAnimalMarketAdd';
    $this->name = clienttranslate('Animal market');
    $this->actionCardType = 'AnimalMarket';
    $this->desc = ['+1<SHEEP> +1<FOOD>', '+1<PIG>', '+1<CATTLE> -1<FOOD>'];
    $this->tooltip = [
      clienttranslate('Choose one: receive 1 sheep and 1 food, or receive 1 wild boar, or buy 1 cattle for 1 food'),
    ];
    $this->container = 'add';

    $this->isAdditional = true;
    $this->flow = [
      'type' => NODE_XOR,
      'childs' => [
        ['action' => GAIN, 'actionId' => 'Market1', 'args' => [SHEEP => 1, FOOD => 1]],
        ['action' => GAIN, 'actionId' => 'Market2', 'args' => [PIG => 1]],
        [
          'actionId' => 'Market3',
          'childs' => [
            [
              'action' => PAY,
              'args' => [
                'nb' => 1,
                'costs' => Utils::formatCost([FOOD => 1]),
                'source' => clienttranslate('Animal market'),
              ],
            ],
            [
              'action' => GAIN,
              'args' => [CATTLE => 1],
            ],
          ],
        ],
      ],
    ];
  }
}
