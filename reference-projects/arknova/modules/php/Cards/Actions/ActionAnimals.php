<?php

namespace ARK\Cards\Actions;

class ActionAnimals extends \ARK\Models\ActionCard
{
  public function __construct($row)
  {
    parent::__construct($row);
    $this->actionType = 'Animals';
    $this->name = clienttranslate('Animals');
    $this->descI = [clienttranslate('Play animals cards from your **hand**.'), 'ANIMALS-I'];
    $this->descII = [
      clienttranslate('Play animals cards from your **hand** or from within **reputation range** (with additional costs).'),
      'ANIMALS-II',
    ];
    $this->tooltip = [
      clienttranslate('At level I, you can play animal cards only from your hand <HAND-CARDS>. At level II, you can play cards either from your hand or in your reputation range by paying **the folder number as additional cost** <HAND-CARDS> <TAKE-IN-RANGE-FOLDER-COST>.')
    ];
  }

  public function getBaseNode($strength = null)
  {
    $strength = $strength ?? $this->getCurrentStrength();
    $flowMap = [1 => [0, 0, 1, 1, 1, 2], 2 => [0, 1, 1, 2, 2, 2]];
    $max = $flowMap[$this->getLevel()][$strength] ?? 2;
    $animalNode =  [
      'action' => ANIMALS,
      'args' => [
        'strength' => $strength,
        'lvl' => $this->getLevel(),
        'max' => $max,
        'number' => $this->number
      ],
    ];

    return [$animalNode, $max];
  }

  public function getFlow($strength = null)
  {
    list($animalNode, $max) = $this->getBaseNode($strength);

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

  public function canBePlayed($player, $strength = null)
  {
    $strength = $strength ?? $this->getStrength();
    if ($strength >= 5 && $this->getLevel() == 2) {
      return true;
    } else {
      return parent::canBePlayed($player, $strength);
    }
  }
}
