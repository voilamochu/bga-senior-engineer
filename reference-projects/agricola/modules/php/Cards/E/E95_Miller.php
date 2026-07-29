<?php

namespace AGR\Cards\E;

use AGR\Models\Occupation;
use AGR\Helpers\Utils;

class E95_Miller extends Occupation
{
  public function __construct($row)
  {
    parent::__construct($row);
    $this->id = 'E95_Miller';
    $this->name = clienttranslate('Miller');
    $this->deck = 'E';
    $this->author = 'tom';
    $this->number = 95;
    $this->category = 'ACTION_-_MAJOR_IMPROVEMENT';
    $this->desc = [
      clienttranslate(
        'You can immediately build a <BAKE>-improvement by paying its cost. Each time another player uses the __Grain Seeds__ action space, you can take a __Bake Bread__ action.'
      ),
    ];
    $this->players = '1+';
  }

  public function onBuy($player)
  {
    return Utils::wrapOptional([
      'action' => IMPROVEMENT,
      'args' => [
        'types' => [MINOR, MAJOR],
        'allowedPurchases' => [
          'Major_Fireplace1',
          'Major_Fireplace2',
          'Major_CookingHearth1',
          'Major_CookingHearth2',
          'Major_ClayOven',
          'Major_StoneOven',
          'D59_EarthOven',
          'A60_OrientalFireplace',
          'D25_WitchesDanceFloor',
          'D64_BakingCourse',
          'E63_IronOven',
          'E64_SimpleOven'
        ],
        'trueAction' => false,
      ]
    ]);
  }

  public function isListeningTo($event)
  {
    return $this->isActionEvent($event, 'PlaceFarmer', 'opponent') && $event['actionCardType'] == 'GrainSeeds';
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
              'action' => EXCHANGE,
              'optional' => false,
              'pId' => $this->pId,
              'args' => [
                'trigger' => BREAD,
              ],
            ]
          ]
        ]
      ]
    ];
  }
}
