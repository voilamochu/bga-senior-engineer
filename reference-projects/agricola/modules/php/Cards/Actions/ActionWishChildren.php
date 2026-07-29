<?php
namespace AGR\Cards\Actions;
use AGR\Helpers\Utils;

class ActionWishChildren extends \AGR\Models\ActionCard
{
  public function __construct($row)
  {
    parent::__construct($row);
    $this->id = 'ActionWishChildren';
    $this->name = clienttranslate('Wish for Children');
    $this->desc = [clienttranslate('<CHILD> [▷] 1<MINOR>')];
    $this->tooltipDesc = [
      clienttranslate('{{ <CHILD> [Growth with room only] }}'),
      clienttranslate('[then]'),
      '1<MINOR>',
    ];
    $this->tooltip = [
      clienttranslate(
        'You can only use this action space to grow your family if you currently have more rooms than people, regardless of whether these people are still at home or on action spaces.'
      ),
      clienttranslate('You may not skip the family growth only to play a minor improvement.'),
    ];

    $this->isNotBeginner = true;
    $this->stage = 2;
    $this->flow = [
      'type' => NODE_SEQ,
      'childs' => [
        [
          'action' => WISHCHILDREN,
          'args' => ['constraints' => ['freeRoom'], 'fromActionSpace' => true],
        ],
        Utils::wrapOptional([
          'action' => IMPROVEMENT,
          'args' => [
            'types' => [MINOR],
          ],
        ]),
      ],
    ];
  }
}
