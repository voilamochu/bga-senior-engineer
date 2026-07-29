<?php

namespace ARK\Cards\Actions;

class ActionAnimals1 extends ActionAnimals
{
  public function __construct($row)
  {
    parent::__construct($row);
    $this->number = 1;
    $this->name = clienttranslate('Animals');
    $this->descI = [clienttranslate('Play animals cards.'), 'ANIMALS-I1', clienttranslate('If you play only 1 animal at <STRENGTH:5>, you may **ignore 1 condition**.')];
    $this->descII = [
      clienttranslate('Play animals cards.'),
      'ANIMALS-II1',
      clienttranslate('If you play only 1 animal at <STRENGTH:3> or more, you may **ignore 1 condition**')
    ];
    $this->tooltip[] = clienttranslate("If the strength of the action allows you to play 2 Animal cards, but you play only 1, you may ignore 1 condition on that Animal card. (<SIDE_I> strength 5; <SIDE_II> strength 3+)");
  }

  public function canBePlayed($player, $strength = null)
  {
    $strength = $strength ?? $this->getStrength();
    if ($strength >= 5 && $this->getLevel() == 2) {
      return true;
    } else {
      $flowMap = [1 => [0, 0, 1, 1, 1, 2], 2 => [0, 1, 1, 2, 2, 2]];
      $max = $flowMap[$this->getLevel()][$strength] ?? 2;

      return $this->getAction([
        'strength' => $strength,
        'lvl' => $this->getLevel(),
        'ignore' => $max == 2 ? 1 : 0
      ])->isDoable($player);
    }
  }

  public function getFlow($strength = null)
  {
    list($animalNode, $max) = $this->getBaseNode($strength);

    // MAX = 2 => XOR for 1 animal while ignoring 1 condition
    if ($max == 2) {
      $animalNode = [
        'type' => NODE_XOR,
        'childs' => [
          $animalNode,
          [
            'action' => ANIMALS,
            'args' => ['strength' => $strength, 'lvl' => $this->getLevel(), 'number' => $this->number, 'max' => 1, 'ignore' => 1],
          ]
        ]
      ];
    }

    // STRENGTH 5 LVL 2 => 1 REP
    if ($this->getLevel() == 2 && $strength >= 5) {
      $flow = [
        'type' => NODE_SEQ,
        'childs' => [
          ['action' => GAIN, 'args' => [REPUTATION => 1], 'source' => clienttranslate('max strength Animals')],
          $animalNode,
        ],
      ];
    } else {
      $flow = $animalNode;
    }

    return $flow;
  }
}
