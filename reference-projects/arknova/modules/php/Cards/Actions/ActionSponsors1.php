<?php

namespace ARK\Cards\Actions;

class ActionSponsors1 extends ActionSponsors
{
  public function __construct($row)
  {
    parent::__construct($row);
    $this->number = 1;
    $this->descI = [
      clienttranslate('Play **1** sponsors card with a maximum level of <STRENGTH:X>.'),
      'SPONSORS',
      clienttranslate('Break <STRBREAK:X>, gain <MONEY:X>.'),
      'SEPARATOR',
      clienttranslate('Additionally, you may trade 1 <XTOKEN> marker for <MONEY:5> or vice versa.')
    ];
    $this->descII = [
      clienttranslate(
        'Play **1 or more** sponsors cards with a total maximum level of <STRENGTH:X>** + 1**.'
      ),
      'SPONSORS',
      clienttranslate('Break <STRBREAK:X>, gain **2x**<MONEY:X>.'),
      'SEPARATOR',
      clienttranslate('Additionally, you may trade 1 <XTOKEN> marker for <MONEY:5> or vice versa **or** pay either for <REPUTATION:1>.')
    ];
    $this->tooltip[] = clienttranslate("<SIDE_I> In addition to your action, you may pay 1 X-token to gain 5 money or pay 5 money to gain 1 X-token. You may do this before or after your Sponsors action. You may do this no matter whether you play a Sponsor card or choose the Break option. You may not use the new X-token to increase the strength of this Sponsors action.");
    $this->tooltip[] = clienttranslate("<SIDE_II> As Side I OR you may pay 1 X-token or 5 money to gain 1 reputation.");
  }

  public function getFlow($strength = null)
  {
    $flow = parent::getFlow($strength);
    return [
      'type' => NODE_PARALLEL,
      'childs' => [
        $flow,
        [
          'action' => TRADE,
          'args' => ['n' => 1, REPUTATION => $this->getLevel() == 2]
        ]
      ]
    ];
  }
}
