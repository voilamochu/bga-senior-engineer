<?php

namespace ARK\Cards\Sponsors;

class S219_DiversityResearcher extends \ARK\Models\Sponsor
{
  public function __construct($row)
  {
    parent::__construct($row);
    $this->id = 'S219_DiversityResearcher';
    $this->number = 219;
    $this->name = clienttranslate('Diversity Researcher');
    $this->lvl = 5;
    $this->categories = [SCIENCE];
    $this->prerequisites = [\UPGRADED_SPONSORS_CARD => 1];
    $this->effects = [
      IMMEDIATE => [
        clienttranslate(
          'Gain 2 money for each water and rock icon in your zoo. You can find these on cards 241 and 242 and otherwise as requirements in the upper-left corners of cards.'
        ),
      ],
      PASSIVE => [
        clienttranslate(
          'You may cover water and rock spaces. You do not have to do this for your zoo map to be considered completely covered. You may ignore all requirements regarding water and rock spaces when placing unique buildings and playing Animal cards. However, the water and rock icons still count (e. g. for the final scoring of this card). Covered water and rock spaces no longer count for cards 241 and 242, or for final scoring of card 004.'
        ),
      ],
      ENDGAME => [
        clienttranslate('Gain 2 appeal for each set of 1 water icon and 1 rock icon in your zoo (up to a maximum of 3 sets).'),
      ],
    ];
    $this->person = true;
  }

  public function getImmediate()
  {
    $icons = $this->getPlayer()->countCardIcons();
    $n = $icons[ROCK] + $icons[WATER];
    return $n == 0 ? [] : [[MONEY => 2 * $n]];
  }

  public function score()
  {
    $player = $this->getPlayer();
    $icons = $player->countCardIcons();
    $set = min(3, min($icons[WATER], $icons[ROCK]));
    if ($set > 0) {
      $player->incAppeal($set * 2, true, $this->getName());
    }
  }
}
