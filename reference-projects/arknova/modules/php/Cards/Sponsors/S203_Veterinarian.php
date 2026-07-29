<?php

namespace ARK\Cards\Sponsors;

class S203_Veterinarian extends \ARK\Models\Sponsor
{
  public function __construct($row)
  {
    parent::__construct($row);
    $this->id = 'S203_Veterinarian';
    $this->number = 203;
    $this->name = clienttranslate('Veterinarian');
    $this->lvl = 4;
    $this->categories = [SCIENCE];
    $this->effects = [
      IMMEDIATE => [clienttranslate('Gain 2/5/10 money for 1/2/3 universities in your zoo.')],
      PASSIVE => [
        clienttranslate(
          'Supporting a conservation project with the Association action now only requires strength 4 (instead of 5).'
        ),
      ],
      ENDGAME => [clienttranslate('Gain 1 conservation point if you have 3 universities in your zoo.')],
    ];
    $this->person = true;
  }

  public function getImmediate()
  {
    $map = [0 => 0, 1 => 2, 2 => 5, 3 => 10];
    $n = $this->getPlayer()->countUniversities();
    return $n == 0 ? [] : [[MONEY => $map[$n]]];
  }

  public function score()
  {
    $player = $this->getPlayer();
    $n = $this->getPlayer()->countUniversities();
    $this->scoreConservation($n, [3 => 1]);
  }
}
