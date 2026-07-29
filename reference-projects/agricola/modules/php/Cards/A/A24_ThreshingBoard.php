<?php
namespace AGR\Cards\A;

use AGR\Helpers\UsedText;

class A24_ThreshingBoard extends \AGR\Models\MinorImprovement
{
  public function __construct($row)
  {
    parent::__construct($row);
    $this->id = 'A24_ThreshingBoard';
    $this->name = clienttranslate('Threshing Board');
    $this->deck = 'A';
    $this->number = 24;
    $this->category = ACTIONS_BOOSTER;
    $this->desc = [
      clienttranslate(
        'Each time you use the __Farmland__ or __Cultivation__ action space, you get an additional __Bake Bread__ action.'
      ),
    ];
    $this->vp = 1;
    $this->cost = [
      WOOD => 1,
    ];
    $this->prerequisite = clienttranslate('2 Occupations');
    $this->occupationPrerequisites = ['min' => 2];
    $this->usedText = UsedText::get('TIMES_BAKED');
  }

  public function isListeningTo($event)
  {
    return $this->isActionCardEvent($event, 'Farmland') || $this->isActionCardEvent($event, 'Cultivation');
  }

  public function onPlayerPlaceFarmer($player, $event)
  {
    return [
      'countAsUse' => true,
      'type' => NODE_SEQ,
      'optional' => true,
      'childs' => [
        [
          'action' => EXCHANGE,
          'optional' => true,
          'pId' => $player->getId(),
          'args' => [
            'trigger' => BREAD,
          ],
        ],
      ],
    ];
  }
}
