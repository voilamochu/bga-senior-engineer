<?php

namespace ARK\Cards\Sponsors;

class S214_ExpertOnAfrica extends \ARK\Models\Sponsor
{
  public function __construct($row)
  {
    parent::__construct($row);
    $this->id = 'S214_ExpertOnAfrica';
    $this->number = 214;
    $this->name = clienttranslate('Expert On Africa');
    $this->lvl = 4;
    $this->continents = [AFRICA];
    $this->effects = [
      IMMEDIATE => [clienttranslate('Gain 1 appeal for each Africa icon in your zoo.')],
      PASSIVE => [
        clienttranslate(
          'For each Africa icon you play into your zoo, you may place any Action card in card slot 1 after finishing the action.'
        ),
        \clienttranslate(
          'Finish the current action first before using this ability, meaning that the Action card you used to play this card is already in card slot 1 at that time.'
        ),
      ],
      ENDGAME => [clienttranslate('Gain 1 appeal for each X-token in your supply.')],
    ];

    $this->listeningIcon = AFRICA;
    $this->listeningBonuses = [[CLEVER => 1]];
    $this->person = true;
  }

  public function getImmediate()
  {
    return [[APPEAL => AFRICA]];
  }

  public function score()
  {
    $player = $this->getPlayer();
    $nXTokens = $player->countXTokens();
    if ($nXTokens > 0) {
      $player->incAppeal($nXTokens, true, $this->getName());
    }
  }
}
