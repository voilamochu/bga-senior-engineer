<?php
namespace ARK\Cards\Sponsors;

class S261_GuidedSchoolTours extends \ARK\Models\Sponsor
{
  public function __construct($row)
  {
    parent::__construct($row);
    $this->id = 'S261_GuidedSchoolTours';
    $this->number = 261;
    $this->name = clienttranslate('Guided School Tours');
    $this->lvl = 3;
    $this->appeal = 1;
    $this->conservation = 1;
    $this->effects = [
      ENDGAME => [
        clienttranslate(
          'Gain 1 conservation point if you have 5 or more animal category icons in your zoo (including Bear and Petting Zoo Animal).'
        ),
      ],
    ];
  }

  public function score()
  {
    $player = $this->getPlayer();
    $icons = $player->countCardIcons(true, \ANIMAL_TYPES);
    $nTypes = count($icons);
    $this->scoreConservation($nTypes, [5 => 1]);
  }
}
