<?php

namespace ARK\Cards\FinalScoring;

use ARK\Managers\ZooCards;

class F013_SpecializedHabitatZoo extends \ARK\Models\FinalScoring
{
  public function __construct($row)
  {
    parent::__construct($row);
    $this->supported = [MW];
    $this->id = 'F013_SpecializedHabitatZoo';
    $this->number = 13;
    $this->name = clienttranslate('Specialized Habitat Zoo');
    $this->icon = 'ONE-CONTINENT';
    $this->desc = clienttranslate('Choose 1 **continent icon** you did **not support a Base Conservation Project** with. Gain <CONSERVATION> for those icons.');
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

    $iconsToCheck = array_diff(CONTINENTS, $supportedTypes);
    $icons = $player->countCardIcons(true, $iconsToCheck);

    return empty($icons) ? 0 : max($icons);
  }
}
