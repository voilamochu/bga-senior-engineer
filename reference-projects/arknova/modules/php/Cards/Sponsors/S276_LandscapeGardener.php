<?php

namespace ARK\Cards\Sponsors;

class S276_LandscapeGardener extends \ARK\Models\Sponsor
{
  public function __construct($row)
  {
    parent::__construct($row);
    $this->supported = [MW];
    $this->id = 'S276_LandscapeGardener';
    $this->number = 276;
    $this->name = clienttranslate('Landscape Gardener');
    $this->lvl = 6;
    $this->effects = [
      IMMEDIATE => [
        clienttranslate('You may place 1 pavilion for free (the usual building rules apply).'),
        clienttranslate('Also gain 1 appeal for every pavilion in your zoo (so for the free pavilion you usually gain a total of 2 appeal).')
      ],
      PASSIVE => [
        clienttranslate('For each pavilion you build or place, gain 1 X-token.'),
        clienttranslate('You cannot gain more than 1 X-token per action or break (from income effects) that way.')
      ],
    ];
    $this->person = true;
  }

  public function getImmediate()
  {
    return [
      [PAVILION => 1],
      [APPEAL => PAVILION]
    ];
  }
}
