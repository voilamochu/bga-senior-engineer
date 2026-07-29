<?php

namespace AGR\Cards\A;

use AGR\Models\Occupation;
use const NODE_SEQ;

class A158_CulinaryArtist extends Occupation
{
  public function __construct($row)
  {
    parent::__construct($row);
    $this->id = 'A158_CulinaryArtist';
    $this->name = clienttranslate('Culinary Artist');
    $this->deck = 'A';
    $this->number = 158;
    $this->category = FOOD_PROVIDER;
    $this->desc = [
      clienttranslate(
        'Each time another player uses the __Traveling Players__ accumulation space, you can exchange your choice of 1 <GRAIN>/<SHEEP>/<VEGETABLE> for 4/5/7 <FOOD>.'
      ),
    ];
    $this->players = '4+';
    $this->isArtifexOrBubulcus = true;
  }

  public function isListeningTo($event)
  {
    return $this->isActionCardEvent($event, 'TravelingPlayers', 'opponent', true);
  }

  public function onOpponentImmediatelyAfterPlaceFarmer($player, $event)
  {
    $bumpUsedNode = [
      'action' => SPECIAL_EFFECT,
      'args' => [
        'cardId' => $this->id,
        'method' => 'bumpUsed',
      ],
    ];
    $wrapBranch = function ($branch) use ($bumpUsedNode) {
      return [
        'type' => NODE_SEQ,
        'pId' => $this->pId,
        'childs' => [$branch, $bumpUsedNode],
      ];
    };
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
              'type' => NODE_XOR,
              'optional' => true,
              'doableRequiresChild' => true,
              'customDescription' => 'Pay good, gain <FOOD>',
              'childs' => [
                $wrapBranch($this->payGainNode([GRAIN => 1], [FOOD => 4], null, false)),
                $wrapBranch($this->payGainNode([SHEEP => 1], [FOOD => 5], null, false)),
                $wrapBranch($this->payGainNode([VEGETABLE => 1], [FOOD => 7], null, false)),
              ]
            ],
          ],
        ],
      ],
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