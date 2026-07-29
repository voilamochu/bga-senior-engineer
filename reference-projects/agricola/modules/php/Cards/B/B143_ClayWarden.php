<?php
namespace AGR\Cards\B;
use AGR\Managers\Players;

class B143_ClayWarden extends \AGR\Models\Occupation
{
  public function __construct($row)
  {
    parent::__construct($row);
    $this->id = 'B143_ClayWarden';
    $this->name = clienttranslate('Clay Warden');
    $this->deck = 'B';
    $this->number = 143;
    $this->category = BUILDING_RESOURCE_PROVIDER;
    $this->desc = [
      clienttranslate(
        'Each time another player uses the __Hollow__ accumulation space, you get 1 <CLAY>. In a 3-/4-player game, you also get 1 additional <CLAY>/<FOOD>.'
      ),
    ];
    $this->players = '3+';
    $this->isArtifexOrBubulcus = true;
  }
  
  public function isListeningTo($event)
  {
    return $this->isActionCardEvent($event, 'Hollow', 'opponent', true) ||
      $this->isActionCardEvent($event, 'Hollow4', 'opponent', true);
  }

  public function onOpponentImmediatelyAfterPlaceFarmer($player, $event)
  {
    $playerCount = Players::count();
    $gain[CLAY] = 1;

    if ($playerCount == 3) {
      $gain[CLAY] = 2;
    }
    if ($playerCount == 4) {
      $gain[FOOD] = 1;
    }

    return $this->gainNode($gain);
  }
}
