<?php

namespace ARK\Cards\Sponsors;

class S275_HorseWhisperer extends \ARK\Models\Sponsor
{
  public function __construct($row)
  {
    parent::__construct($row);
    $this->supported = [MW];
    $this->id = 'S275_HorseWhisperer';
    $this->number = 275;
    $this->name = clienttranslate('Horse Whisperer');
    $this->lvl = 3;
    $this->effects = [
      IMMEDIATE => [
        clienttranslate('Search the discard pile for any 1 Petting Zoo Animal of your choice and take it into hand.'),
        clienttranslate('If there are no Petting Zoo Animals in the discard pile, you gain nothing.'),
        clienttranslate('You may not search the discard pile at any other time (for example before playing this card).')
      ],
      PASSIVE => [clienttranslate('For every Petting Zoo Animal icon that is played in any zoo, gain 2 money (per icon).')],
    ];
    $this->prerequisites = [REPUTATION => 6];
    $this->categories = [PET];

    $this->listeningIcon = PET;
    $this->listeningMode = ALL_ZOO;
    $this->listeningBonuses = [[MONEY => 2]];
    $this->person = true;
  }

  public function getImmediate()
  {
    return [[SEARCH_PET_DISCARD => 1]];
  }
}
