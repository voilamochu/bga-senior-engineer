<?php
namespace AGR\Cards\E;

class E124_MayorCandidate extends \AGR\Models\Occupation
{
  public function __construct($row)
  {
    parent::__construct($row);
    $this->id = 'E124_MayorCandidate';
    $this->name = clienttranslate('Mayor Candidate');
    $this->deck = 'E';
    $this->author = 'oxmond';
    $this->number = 124;
    $this->category = 'BUILDING_RESOURCES_-_STONE';
    $this->desc = [
      clienttranslate(
        'You immediately get 2 <WOOD> and 2 <STONE>. During scoring, you get 1 negative point for each <WOOD> and each <STONE> in your supply. You can no longer discard <WOOD> or <STONE>.'
      ),
    ];
    $this->players = '1+';
    $this->extraVp = true;
  }

  public function onBuy($player)
  {
    return $this->gainNode([WOOD => 2, STONE => 2]);
  }

  public function computeBonusScore()
  {
    $player = $this->getPlayer();
    $malus = $player->countReserveResource(WOOD) + $player->countReserveResource(STONE);

    if ($malus > 0) {
      $this->addBonusScoringEntry(-$malus);
    }
  }
}
