<?php
namespace ARK\Cards\Sponsors;

class S225_QuarantineLab extends \ARK\Models\Sponsor
{
  public function __construct($row)
  {
    parent::__construct($row);
    $this->id = 'S225_QuarantineLab';
    $this->number = 225;
    $this->name = clienttranslate('Quarantine Lab');
    $this->lvl = 3;
    $this->categories = [SCIENCE];
    $this->effects = [
      IMMEDIATE => [clienttranslate('Gain 1 X-token. (Remember, you cannot have more than 5 X-tokens at any time.)')],
      PASSIVE => [
        clienttranslate('The effects of Venom, Constriction, Hypnosis, and Pilfering do not affect you.'),
        \clienttranslate(
          'If Hypnosis or Pilfering would affect you, they instead affect the next player who meets the criterion, if possible.'
        ),
        clienttranslate('Essentially, your counters on the tracks are ignored.'),
      ],
      ENDGAME => [clienttranslate('Gain 1 conservation point if you have all 5 continent icons in your zoo.')],
    ];
  }

  public function getImmediate()
  {
    return [[XTOKEN => 1]];
  }

  public function score()
  {
    $player = $this->getPlayer();
    $icons = $player->countCardIcons(true, \CONTINENTS);
    $nTypes = count($icons);
    $this->scoreConservation($nTypes, [5 => 1]);
  }
}
