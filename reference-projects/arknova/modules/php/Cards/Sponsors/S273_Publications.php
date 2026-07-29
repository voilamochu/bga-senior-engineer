<?php

namespace ARK\Cards\Sponsors;

class S273_Publications extends \ARK\Models\Sponsor
{
  public function __construct($row)
  {
    parent::__construct($row);
    $this->supported = [MW];
    $this->id = 'S273_Publications';
    $this->number = 273;
    $this->name = clienttranslate('Publications');
    $this->lvl = 4;
    $this->effects = [
      IMMEDIATE => [
        clienttranslate('You may make 1 donation.'),
        clienttranslate('You do not need to have your Association Action card upgraded to do this. The usual rules apply.')
      ],
      PASSIVE => [clienttranslate('Every time you make a donation, pay 1 money less for each research icon in your zoo (to a minimum of 0 money).')],
    ];
    $this->prerequisites = [SCIENCE => 1];
    $this->categories = [SCIENCE];
  }

  public function getImmediate()
  {
    return [[DONATE => 1]];
  }
}
