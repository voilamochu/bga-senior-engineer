<?php
namespace AGR\Cards\D;
use AGR\Helpers\Utils;
use AGR\Managers\Players;

class D154_ChimneySweep extends \AGR\Models\Occupation
{
  public function __construct($row)
  {
    parent::__construct($row);
    $this->id = 'D154_ChimneySweep';
    $this->name = clienttranslate('Chimney Sweep');
    $this->deck = 'D';
    $this->number = 154;
    $this->isCorbariusOrDulcinaria = true;
    $this->category = POINTS_PROVIDER;
    $this->desc = [
      clienttranslate(
        'Renovating to stone costs you 2 <STONE> less. During scoring, you get 1 bonus <SCORE> for each other player living in a stone house.'
      ),
    ];
    $this->players = '4+';
    $this->extraVp = true;
  }

  public function orderComputeCostsRenovation()
  {
    return [['>', 'B145_BrushwoodCollector'], ['<', 'A143_Stonecutter']];
  }

  public function onPlayerComputeCostsRenovation($player, &$args)
  {
    Utils::addBonus($args['costs'], [STONE => -2], $this->id);
  }

  public function computeBonus()
  {
    $nb = 0;
    foreach (Players::getAll() as $pId => $player) {
      if ($pId != $this->pId && $player->getRoomType() == 'roomStone') {
        $nb++;
      }
    }
    return $nb;
  }

  public function computeBonusScore()
  {
    $this->addBonusScoringEntry($this->computeBonus());
  }
}
