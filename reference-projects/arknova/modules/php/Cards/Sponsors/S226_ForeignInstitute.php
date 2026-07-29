<?php
namespace ARK\Cards\Sponsors;

class S226_ForeignInstitute extends \ARK\Models\Sponsor
{
  public function __construct($row)
  {
    parent::__construct($row);
    $this->id = 'S226_ForeignInstitute';
    $this->number = 226;
    $this->name = clienttranslate('Foreign Institute');
    $this->lvl = 6;
    $this->reputation = 2;
    $this->categories = [SCIENCE];
    $this->effects = [
      ENDGAME => [clienttranslate('Gain 1 conservation point if you have all 5 continent icons in your zoo.')],
    ];
  }

  public function score()
  {
    $player = $this->getPlayer();
    $icons = $player->countCardIcons(true, \CONTINENTS);
    $nTypes = count($icons);
    $this->scoreConservation($nTypes, [5 => 1]);
  }
}
