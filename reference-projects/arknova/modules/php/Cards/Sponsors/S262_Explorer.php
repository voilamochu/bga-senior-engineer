<?php

namespace ARK\Cards\Sponsors;

class S262_Explorer extends \ARK\Models\Sponsor
{
  public function __construct($row)
  {
    parent::__construct($row);
    $this->id = 'S262_Explorer';
    $this->number = 262;
    $this->name = clienttranslate('Explorer');
    $this->lvl = 5;
    $this->prerequisites = [UPGRADED_SPONSORS_CARD => 1];
    $this->effects = [
      IMMEDIATE => [
        clienttranslate(
          'Gain 2 money for each different continent or animal category icon in your zoo (including Bear and Petting Zoo Animal).'
        ),
        clienttranslate(
          'Example: If you have 2 research icons, 2 Africa icons, and 1 predator icon in your zoo, you gain 4 money.'
        ),
      ],
      PASSIVE => [
        clienttranslate(
          'Every time you play a continent or animal category icon into your zoo (including Bear and Petting Zoo Animal) that is not already in your zoo, you gain 1 appeal and 2 money.'
        ),
        clienttranslate(
          'If you play a card that has 2 of the same applicable icon on it, you still only receive this bonus once.'
        ),
      ],
    ];
    $this->person = true;
  }

  public function getImmediate()
  {
    $icons = $this->getPlayer()->countCardIcons(true, CONTINENTS_AND_TYPES);
    $n = count($icons);
    return $n == 0 ? [] : [[MONEY => 2 * $n]];
  }

  public function getIconsReaction($icons, $isOwnZoo)
  {
    if (!$isOwnZoo) {
      return [];
    }

    $newIcons = 0;
    $playerIcons = $this->getPlayer()->countCardIcons(true, CONTINENTS_AND_TYPES);
    foreach ($icons as $type => $n) {
      if (($playerIcons[$type] ?? 0) == $n) {
        $newIcons++;
      }
    }

    return $newIcons == 0 ? [] : [[APPEAL => $newIcons], [MONEY => 2 * $newIcons]];
  }
}
