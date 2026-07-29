<?php

namespace ARK\Cards\Sponsors;

class S263_WazaLargeAnimalProgram extends \ARK\Models\Sponsor
{
  public function __construct($row)
  {
    parent::__construct($row);
    $this->id = 'S263_WazaLargeAnimalProgram';
    $this->number = 263;
    $this->name = clienttranslate('Waza Large Animal Program');
    $this->lvl = 5;
    $this->prerequisites = [\UPGRADED_SPONSORS_CARD => 1, REPUTATION => 6];
    $this->effects = [
      IMMEDIATE => [clienttranslate('You may place a 5-space enclosure for free (the usual building rules apply).')],
      PASSIVE => [
        clienttranslate('Every time you play a large animal, you may ignore one condition of your choice on the Animal card.'),
        clienttranslate('Large animals are animals that require a standard enclosure of 4 or 5 spaces.'),
        clienttranslate('You cannot ignore rock or water requirements.'),
      ],
    ];
  }

  public function getImmediate()
  {
    return [[BONUS_SIZE_5_ENCLOSURE => 1]];
  }
}
