<?php

namespace ARK\Cards\Actions;

class ActionSponsors2 extends ActionSponsors
{
  public function __construct($row)
  {
    parent::__construct($row);
    $this->number = 2;
    $this->descI = [
      clienttranslate('Play **1** sponsors card with a maximum level of <STRENGTH:X>.'),
      'SPONSORS',
      clienttranslate('Break <STRBREAK:X>, gain <MONEY:X>.'),
      'SEPARATOR',
      clienttranslate('If you **gain money** during this action, gain <MONEY:3> more.')
    ];
    $this->descII = [
      clienttranslate(
        'Play **1 or more** sponsors cards with a total maximum level of <STRENGTH:X>** + 1**.'
      ),
      'SPONSORS',
      clienttranslate('Break <STRBREAK:X>, gain **2x**<MONEY:X>.'),
      'SEPARATOR',
      clienttranslate('If you **gain money** during this action, gain <MONEY:5> more.')
    ];
    $this->tooltip[] = clienttranslate("Gain X extra money when you gain money because of this Sponsors action (no matter how much money and from how many sources). That includes using the break option, playing a Sponsor card that gives you money itself, and any effects giving you money that are triggered because of a Sponsor card you play. (<SIDE_I> X=3 money; <SIDE_II> X=5 money)");
  }
}
