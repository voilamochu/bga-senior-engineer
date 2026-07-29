<?php

namespace AGR\Cards\C;

use AGR\Managers\Farmers;
use AGR\Models\Occupation;
use const NODE_XOR;

class C97_SeedResearcher extends Occupation
{
  public function __construct($row)
  {
    parent::__construct($row);
    $this->id = 'C97_SeedResearcher';
    $this->name = clienttranslate('Seed Researcher');
    $this->deck = 'C';
    $this->number = 97;
    $this->category = ACTIONS_BOOSTER;
    $this->desc = [
      clienttranslate(
        'Each time any people return from both the __Grain Seeds__ and __Vegetable Seeds__ action spaces, you get 2 <FOOD> and you can play 1 occupation, without paying an occupation cost.'
      ),
    ];
    $this->players = '1+';
    $this->isCorbariusOrDulcinaria = true;
  }

  public function isListeningTo($event)
  {
    return $this->isPlayerEvent($event) &&
      $event['type'] == 'StartReturnHome' &&
      !Farmers::getOnCard('ActionGrainSeeds')->empty() &&
      !Farmers::getOnCard('ActionVegetableSeeds')->empty();
  }

  public function onPlayerStartReturnHome($player, $event)
  {
    if (count($player->getHand(OCCUPATION)) > 0) {
      return [
        'countAsUse' => true,
        'type' => NODE_XOR,
        'childs' => [
          [
            'type' => NODE_SEQ,
            'childs' => [$this->gainNode([FOOD => 2]),],
          ],
          [
            'type' => NODE_SEQ,
            'childs' => [
              $this->gainNode([FOOD => 2]),
              [
                'action' => OCCUPATION,
                'optional' => false,
                'source' => $this->name,
                'args' => ['cost' => []],
              ]
            ],
          ],
        ],
      ];
    }

    // If no occ in hand
    return $this->gainNode([FOOD => 2]);
  }
}
