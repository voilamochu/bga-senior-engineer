<?php

namespace ARK\Cards\FinalScoring;

use ARK\Managers\ZooCards;

class F014_SpecializedSpeciesZoo extends \ARK\Models\FinalScoring
{
  public function __construct($row)
  {
    parent::__construct($row);
    $this->supported = [MW];
    $this->id = 'F014_SpecializedSpeciesZoo';
    $this->number = 14;
    $this->name = clienttranslate('Specialized Species Zoo');
    $this->icon = 'ONE-ANIMAL';
    $this->desc = clienttranslate('Choose 1 **animal category icon** you did **not support a Base Conservation Project** with. Gain <CONSERVATION> for those icons.');
    $this->scoreMap = [3 => 1, 4 => 2, 5 => 3, 6 => 4];
  }

  public function getQuantity()
  {
    $player = $this->getPlayer();
    $supportedTypes = [];
    foreach (ZooCards::getBaseProjects() as $card) {
      if ($player->countCardTokens($card->getId()) == 0) continue;

      $supportedTypes[] = $card->getIcon();
    }

    $iconsToCheck = array_diff(ANIMAL_TYPES, $supportedTypes);
    $icons = $player->countCardIcons(true, $iconsToCheck);

    return empty($icons) ? 0 : max($icons);
  }
}
