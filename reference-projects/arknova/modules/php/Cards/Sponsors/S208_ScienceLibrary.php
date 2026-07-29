<?php

namespace ARK\Cards\Sponsors;

class S208_ScienceLibrary extends \ARK\Models\Sponsor
{
  public function __construct($row)
  {
    parent::__construct($row);
    $this->id = 'S208_ScienceLibrary';
    $this->number = 208;
    $this->name = clienttranslate('Science Library');
    $this->lvl = 4;
    $this->categories = [SCIENCE];
    $this->effects = [
      IMMEDIATE => [clienttranslate('Gain 1 appeal for each research icon in your zoo.')],
      PASSIVE => [clienttranslate('For each research icon that is played in any zoo, gain 2 money.')],
      ENDGAME => [
        clienttranslate('Gain 1 conservation point if you have at least 5 different animal category icons in your zoo.'),
      ],
    ];

    $this->listeningIcon = SCIENCE;
    $this->listeningMode = ALL_ZOO;
    $this->listeningBonuses = [[MONEY => 2]];
  }

  public function getImmediate()
  {
    return [[APPEAL => SCIENCE]];
  }

  public function score()
  {
    $player = $this->getPlayer();
    $icons = $player->countCardIcons(true, \ANIMAL_TYPES);
    $nTypes = count($icons);
    $this->scoreConservation($nTypes, [5 => 1]);
  }
}
