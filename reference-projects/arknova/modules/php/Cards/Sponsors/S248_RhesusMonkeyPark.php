<?php
namespace ARK\Cards\Sponsors;

class S248_RhesusMonkeyPark extends UniqueBuildingSponsor
{
  public function __construct($row)
  {
    parent::__construct($row);
    $this->id = 'S248_RhesusMonkeyPark';
    $this->number = 248;
    $this->name = clienttranslate('Rhesus Monkey Park');
    $this->lvl = 5;
    $this->enclosure = 'monkey';
    $this->categories = [PRIMATE];
    $this->effects = [
      IMMEDIATE => [clienttranslate('Place the Rhesus Monkey Park unique building on your zoo map.')],
      PASSIVE => [
        clienttranslate(
          'For each primate icon you play into your zoo, gain 1 X-token. (Remember, you cannot have more than 5 X-tokens at any time).'
        ),
      ],
    ];
    $this->listeningIcon = \PRIMATE;
    $this->listeningBonuses = [[XTOKEN => 1]];
  }
}
