<?php

namespace AGR\Cards\A;

use AGR\Helpers\UsedText;
use AGR\Cards\B\B85_FarmHand;
use AGR\Helpers\Utils;
use AGR\Managers\Players;
use AGR\Models\Occupation;

class A150_Stagehand extends Occupation
{
  public function __construct($row)
  {
    parent::__construct($row);
    $this->id = 'A150_Stagehand';
    $this->name = clienttranslate('Stagehand');
    $this->deck = 'A';
    $this->number = 150;
    $this->category = ACTIONS_BOOSTER;
    $this->desc = [
      clienttranslate(
        'Each time another player uses the __Traveling Players__ accumulation space, you can take your choice of a __Build Fences__, __Build Stables__, or __Build Rooms__ action.'
      ),
    ];
    $this->players = '4+';
    $this->isArtifexOrBubulcus = true;
    $this->usedText = UsedText::get('BONUS_ACTIONS');
  }

  public function isListeningTo($event)
  {
    return $this->isActionEvent($event, 'PlaceFarmer', 'opponent') && $event['actionCardType'] == 'TravelingPlayers';
  }

  public function onOpponentAfterPlaceFarmer($player, $args)
  {
    $me = Players::get($this->pId); // the Stagehand owner, not the opponent
    $stablesFlow = B85_FarmHand::wrapStablesWithFarmHandIfPlayed($me, [
      'action' => STABLES,
      'source' => $this->name,
      'args' => ['costs' => Utils::formatCost([WOOD => 2])],
      'pId' => $this->pId,
    ]);

    $bumpUsedNode = [
      'action' => SPECIAL_EFFECT,
      'args' => [
        'cardId' => $this->id,
        'method' => 'bumpUsed',
      ],
    ];
    return [
      'type' => NODE_SEQ,
      'pId' => $this->pId,
      'childs' => [
        [
          'type' => NODE_XOR,
          'optional' => true,
          'forceConfirmation' => true,
          'pId' => $this->pId,
          'childs' => [
            [
              'type' => NODE_SEQ,
              'pId' => $this->pId,
              'childs' => [
                [
                  'action' => FENCING,
                  'source' => $this->name,
                  'args' => ['costs' => Utils::formatCost([WOOD => 1])],
                  'pId' => $this->pId,
                ],
                $bumpUsedNode,
              ],
            ],
            [
              'type' => NODE_SEQ,
              'pId' => $this->pId,
              'childs' => [
                $stablesFlow,
                $bumpUsedNode,
              ],
            ],
            [
              'type' => NODE_SEQ,
              'pId' => $this->pId,
              'childs' => [
                [
                  'action' => CONSTRUCT,
                  'source' => $this->name,
                  'pId' => $this->pId,
                ],
                $bumpUsedNode,
              ],
            ],
          ]
        ]
      ]
    ];
  }

  public function bumpUsed()
  {
    $this->incStats('used');
  }

  public function isIndependentBumpUsed()
  {
    return true;
  }
}

