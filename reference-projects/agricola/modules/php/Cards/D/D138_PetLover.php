<?php
namespace AGR\Cards\D;
use AGR\Managers\Meeples;
use AGR\Helpers\UserException;

class D138_PetLover extends \AGR\Models\Occupation
{
  public function __construct($row)
  {
    parent::__construct($row);
    $this->id = 'D138_PetLover';
    $this->name = clienttranslate('Pet Lover');
    $this->deck = 'D';
    $this->number = 138;
    $this->isCorbariusOrDulcinaria = true;
    $this->category = GOODS_PROVIDER;
    $this->desc = [
      clienttranslate(
        'Each time you use an accumulation space providing exactly 1 animal, you can leave it on the space and get one from the general supply instead, as well as 3 <FOOD> and 1 <GRAIN>.'
      ),
    ];
    $this->players = '3+';

    $this->rulings = [
      clienttranslate('You may use __Animal Dealer__ (A147) to acquire a second animal of the taken type.'),
    ];
  }

  public function onPlayerComputePlaceFarmerFlow($player, &$args)
  {
    $map = ['SheepMarket' => SHEEP, 'PigMarket' => PIG, 'CattleMarket' => CATTLE];
    if (!in_array($args['actionCardType'], ['SheepMarket', 'PigMarket', 'CattleMarket'])) {
      return;
    }

    $animals = Meeples::getResourcesOnCard($args['actionCardId'], null, SHEEP)->count() +
               Meeples::getResourcesOnCard($args['actionCardId'], null, PIG)->count() +
               Meeples::getResourcesOnCard($args['actionCardId'], null, CATTLE)->count();
    if ($animals != 1) {
      return;
    }
    $type = $map[$args['actionCardType']];
    $flow = $args['flow'];
    $flow['pId'] = $this->pId;

    $args['flow'] = [
      'type' => NODE_XOR,
      'pId' => $this->pId,
      'childs' => [
        $flow,
        [
          'type' => NODE_SEQ,
          'pId' => $this->pId,
          'childs' => [
            $this->gainNode([$type => 1, FOOD => 3, GRAIN => 1], $this->pId),
            [
              'action' => SPECIAL_EFFECT,
              'args' => [
                'cardId' => $this->id,
                'method' => 'bumpUsed',
              ],
            ],
          ],
        ],
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
