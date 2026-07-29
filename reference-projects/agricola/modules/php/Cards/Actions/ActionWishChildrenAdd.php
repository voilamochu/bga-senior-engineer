<?php
namespace AGR\Cards\Actions;
use AGR\Managers\Farmers;

class ActionWishChildrenAdd extends \AGR\Models\ActionCard
{
  public function __construct($row)
  {
    parent::__construct($row);
    $this->id = 'ActionWishChildrenAdd';
    $this->name = clienttranslate('Wish for Children');
    $this->actionCardType = 'WishChildren';
    $this->desc = ['<CHILD>'];
    $this->tooltip = [
      clienttranslate(
        'From round 5 (inclusive), you can grow your family on this action space, provided you have enough room.'
      ),
    ];
    $this->container = 'add';
    $this->size = 's';
    //    $this->tooltipDesc = ['<CHILD>' . clienttranslate('Growth with room only'), clienttranslate('not before turn 5')];

    $this->isAdditional = true;
    $this->flow = [
      'action' => WISHCHILDREN,
      'args' => ['constraints' => ['turn5', 'freeRoom'], 'fromActionSpace' => true],
    ];
  }
}
