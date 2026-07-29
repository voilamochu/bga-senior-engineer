<?php
namespace AGR\Cards\A;

class A171_Sidekick extends \AGR\Models\Occupation
{
  public function __construct($row)
  {
    parent::__construct($row);
    $this->id = 'A171_Sidekick';
    $this->name = ('Sidekick');
    $this->deck = 'A';
    $this->number = 171;
    $this->category = ACTIONS_BOOSTER;
    $this->desc = [
      (
        'Immediately after each time you place a person on an action space card, you can pay 1 food to place another person on the card immediately left to it (and so on).'
      ),
    ];
    $this->players = '5+';
    $this->implemented = false;
  }
}
