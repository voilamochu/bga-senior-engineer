<?php

namespace ARK\Cards\Actions;

class ActionSponsors3 extends ActionSponsors
{
  public function __construct($row)
  {
    parent::__construct($row);
    $this->number = 3;
    $this->descI = [
      clienttranslate('Play **1** sponsors card with a maximum level of <STRENGTH:X>.'),
      'SPONSORS',
      clienttranslate('Break <STRBREAK:X>, gain <MONEY:X>.'),
      'SEPARATOR',
      clienttranslate('Additionally you may discard **1 Sponsor card** to gain <MONEY:4>.')
    ];
    $this->descII = [
      clienttranslate(
        'Play **1 or more** sponsors cards with a total maximum level of <STRENGTH:X>** + 1**.'
      ),
      'SPONSORS',
      clienttranslate('Break <STRBREAK:X>, gain **2x**<MONEY:X>.'),
      'SEPARATOR',
      clienttranslate('Additionally you may **discard 1 card**:'),
      clienttranslate('Gain <MONEY:4> **or** reduce the level of one played card by <STRENGTH:2>')
    ];
    $this->tooltip[] = clienttranslate("<SIDE_I> In addition to your action, you may discard 1 Sponsor card to gain 4 money. You may do this before or after your Sponsors action. You may do this no matter whether you play a Sponsor card or choose the Break option.");
    $this->tooltip[] = clienttranslate("<SIDE_II> Same as Side I, but you may discard any card (not just a Sponsor card) to either gain 4 money (like Side I) or to reduce the level of a Sponsor card you play with this action by 2.");
  }

  public function getFlow($strength = null)
  {
    $flow = parent::getFlow($strength);
    return [
      'type' => NODE_PARALLEL,
      'childs' => [
        $flow,
        [
          'action' => SPONSORS_DISCARD_CARD_GET_BONUS,
          'args' => [
            'number' => $this->getNumber(),
            'lvl' => $this->getLevel(),
          ]
        ]
      ]
    ];
  }
}
