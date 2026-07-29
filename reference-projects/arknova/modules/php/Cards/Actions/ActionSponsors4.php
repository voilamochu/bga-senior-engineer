<?php

namespace ARK\Cards\Actions;

class ActionSponsors4 extends ActionSponsors
{
  public function __construct($row)
  {
    parent::__construct($row);
    $this->number = 4;
    $this->descI = [
      clienttranslate('Play **1** sponsors card with a maximum level of <STRENGTH:X>.'),
      'SPONSORS',
      clienttranslate('Break <STRBREAK:X>, gain <MONEY:X>.'),
      'SEPARATOR',
      clienttranslate('Additionally you may discard **1 Sponsor card** to take 1 Sponsor card from the display into hand.')
    ];
    $this->descII = [
      clienttranslate(
        'Play **1 or more** sponsors cards with a total maximum level of <STRENGTH:X>** + 1**.'
      ),
      'SPONSORS',
      clienttranslate('Break <STRBREAK:X>, gain **2x**<MONEY:X>.'),
      clienttranslate('In addition to the Break, you may **discard 1 card** to <BONUS-SPONSOR>'),
    ];
    $this->tooltip[] = clienttranslate("<SIDE_I> In addition to your action, you may discard 1 Sponsor card to snap any 1 Sponsor card from the display. You may do this before or after your action. You may do this no matter whether you play a Sponsor card or choose the Break option.");
    $this->tooltip[] = clienttranslate("<SIDE_II> When choosing the Break option, you may discard any card (not just a Sponsor card) to play a Sponsor card from your hand by paying X money, where X is the level of the card. You may do this before or after gaining money from the Break option.");
  }

  public function getFlow($strength = null)
  {
    $flow = parent::getFlow($strength);
    if ($this->getLevel() == 2) return $flow;

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
