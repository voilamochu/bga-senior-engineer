<?php
namespace AGR\Cards\C;

class C153_PatternMaker extends \AGR\Models\Occupation
{
  public function __construct($row)
  {
    parent::__construct($row);
    $this->id = 'C153_PatternMaker';
    $this->name = clienttranslate('Pattern Maker');
    $this->deck = 'C';
    $this->number = 153;
    $this->category = POINTS_PROVIDER;
    $this->desc = [
      clienttranslate(
        'Each time another player renovates, you can exchange exactly 2 <WOOD> for 1 <GRAIN>, 1 <FOOD>, and 1 bonus <SCORE>.'
      ),
    ];
    $this->players = '4+';
    $this->extraVp = true;
  }

  public function isListeningTo($event)
  {
    return $this->isActionEvent($event, 'Renovation', 'opponent');
  }

  public function onOpponentAfterRenovation($player, $event)
  {
    return [
      'type' => NODE_SEQ,
      'pId' => $this->pId,
      'childs' => [
        [
          'type' => \NODE_SEQ,
          'optional' => true,
          'forceConfirmation' => true,
          'pId' => $this->pId,
          'childs' => [
            $this->payGainNode([WOOD => 2], [GRAIN => 1, FOOD => 1, SCORE => 1]),
            [
              'action' => SPECIAL_EFFECT,
              'args' => [
                'cardId' => $this->id,
                'method' => 'bumpUsed',
              ],
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
