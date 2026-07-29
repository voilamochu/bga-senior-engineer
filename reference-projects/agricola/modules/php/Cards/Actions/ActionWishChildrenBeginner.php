<?php
namespace AGR\Cards\Actions;

class ActionWishChildrenBeginner extends \AGR\Models\ActionCard
{
  public function __construct($row)
  {
    parent::__construct($row);
    $this->id = 'ActionWishChildrenBeginner';
    $this->name = clienttranslate('Wish for Children');
    $this->actionCardType = 'WishChildren';
    $this->desc = [clienttranslate('<CHILD>')];
    $this->tooltipDesc = [clienttranslate('{{ <CHILD> [Growth with room only] }}')];
    $this->tooltip = [
      clienttranslate(
        'You can only use this action space to grow your family if you currently have more rooms than people, regardless of whether these people are still at home or on action spaces.'
      ),
    ];

    $this->isBeginner = true;
    $this->stage = 2;
    $this->flow = [
      'action' => WISHCHILDREN,
      'args' => ['constraints' => ['freeRoom']],
    ];
  }
}
