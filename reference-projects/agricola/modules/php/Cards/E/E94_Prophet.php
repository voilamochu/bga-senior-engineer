<?php
namespace AGR\Cards\E;

use AGR\Helpers\Utils;

class E94_Prophet extends \AGR\Models\Occupation
{
  public function __construct($row)
  {
    parent::__construct($row);
    $this->id = 'E94_Prophet';
    $this->name = clienttranslate('Prophet');
    $this->deck = 'E';
    $this->author = 'mattthelesser';
    $this->number = 94;
    $this->category = 'ACTION';
    $this->desc = [
      clienttranslate(
        'When you play this card, immediately take a __Renovation__ action. Afterward, you can take a __Build Fences__ action. (Both actions require their usual cost.)'
      ),
    ];
    $this->players = '1+';
  }

  public function onBuy($player)
  {
    return [
      'type' => NODE_SEQ,
      'childs' => [
        [
          'action' => RENOVATION,
          'pId' => $this->pId,
        ],
        [
          'action' => FENCING,
          'optional' => true,
          'args' => [
            'costs' => Utils::formatCost([WOOD => 1]),
          ],
          'pId' => $this->pId,
        ],
      ],
    ];
  }
}