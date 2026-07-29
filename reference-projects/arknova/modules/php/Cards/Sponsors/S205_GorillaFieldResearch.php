<?php
namespace ARK\Cards\Sponsors;

class S205_GorillaFieldResearch extends \ARK\Models\Sponsor
{
  public function __construct($row)
  {
    parent::__construct($row);
    $this->id = 'S205_GorillaFieldResearch';
    $this->number = 205;
    $this->name = clienttranslate('Gorilla Field Research');
    $this->lvl = 3;
    $this->conservation = 1;
    $this->reputation = 2;
    $this->categories = [SCIENCE];
    $this->prerequisites = [
      SCIENCE => 3,
    ];
  }
}
