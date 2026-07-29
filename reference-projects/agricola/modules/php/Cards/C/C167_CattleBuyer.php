<?php

namespace AGR\Cards\C;

use AGR\Helpers\UsedText;
use AGR\Models\Occupation;
use const NODE_XOR;

class C167_CattleBuyer extends Occupation
{
  public function __construct($row)
  {
    parent::__construct($row);
    $this->id = 'C167_CattleBuyer';
    $this->name = clienttranslate('Cattle Buyer');
    $this->deck = 'C';
    $this->number = 167;
    $this->category = LIVESTOCK_PROVIDER;
    $this->desc = [
      clienttranslate(
        'Each time another player uses the __Fencing__ action space, you can buy exactly 1 <SHEEP>/<PIG>/<CATTLE> from the general supply for 1/2/2 <FOOD>.'
      ),
    ];
    $this->players = '4+';
    $this->usedText = UsedText::get('ANIMALS_BOUGHT');
  }

  public function isListeningTo($event)
  {
    return $this->isActionEvent($event, 'PlaceFarmer', 'opponent') && $event['actionCardType'] == 'Fencing';
  }

  public function onOpponentAfterPlaceFarmer($player, $args)
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
          'type' => NODE_XOR,
          'optional' => true,
          'doableRequiresChild' => true,
          'customDescription' => 'Buy animal',
          'forceConfirmation' => true,
          'pId' => $this->pId,
          'childs' => [
            $wrapBranch($this->payGainNode([FOOD => 1], [SHEEP => 1], null, false)),
            $wrapBranch($this->payGainNode([FOOD => 2], [PIG => 1], null, false)),
            $wrapBranch($this->payGainNode([FOOD => 2], [CATTLE => 1], null, false)),
          ]
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
