<?php
namespace AGR\Cards\Actions;

class ActionUrgentWishChildren extends \AGR\Models\ActionCard
{
  public function __construct($row)
  {
    parent::__construct($row);
    $this->id = 'ActionUrgentWishChildren';
    $this->name = clienttranslate('Urgent Wish for Children');
    $this->actionCardType = 'WishChildren';
    $this->desc = ['<CHILD_FREE>'];
    $this->tooltipDesc = [clienttranslate('{{ <CHILD_FREE> [Growth without room] }}')];
    $this->tooltip = [
      clienttranslate('The number of rooms in your house does not matter for this effect.'),
      clienttranslate(
        'Note: If you grow your family on this space and build a single room later, you will not be able to use “Basic Wish for Children”.'
      ),
      clienttranslate('The new room is immediately occupied by the person who did not have a room of their own yet.'),
    ];

    $this->stage = 5;
    $this->flow = [
      'action' => WISHCHILDREN,
    ];
  }
}
