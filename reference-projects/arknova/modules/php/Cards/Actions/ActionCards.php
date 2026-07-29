<?php

namespace ARK\Cards\Actions;

class ActionCards extends \ARK\Models\ActionCard
{
  public function __construct($row)
  {
    parent::__construct($row);
    $this->actionType = 'Cards';
    $this->name = clienttranslate('Cards');
    $this->descI = [
      clienttranslate('**BREAK** <BREAK:2>'),
      clienttranslate('Draw cards from the **deck** OR snap.'),
      'CARDS-I',
    ];
    $this->descII = [
      clienttranslate('**BREAK** <BREAK:2>'),
      clienttranslate('Draw cards from within **reputation range** or from the **deck** OR snap.'),
      'CARDS-II',
    ];
    $this->tooltip = [
      clienttranslate('At level I, you can draw cards only from the deck <TAKE-IN-DECK>. At level II, you can draw either from the deck or in reputation range <TAKE-IN-DECK> <TAKE-IN-RANGE>.')
    ];
  }

  public function getFlow($strength = null)
  {
    $flow = parent::getFlow($strength);
    $flow['args']['parameters'] = $this->getParameters();
    return [
      'type' => NODE_SEQ,
      'childs' => [['action' => ADVANCE_BREAK, 'args' => ['n' => 2]], $flow],
    ];
  }

  public function getParameters()
  {
    return [
      1 => [[], [1, 1, 0], [1, 0, 0], [2, 1, 0], [2, 0, 0], [3, 1, 1]],
      2 => [[], [1, 0, 0], [2, 1, 0], [2, 0, 1], [3, 1, 1], [4, 1, 1]],
    ];
  }
}
