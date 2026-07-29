<?php
namespace AGR\Cards\B;
use AGR\Core\Globals;

class B140_FarmyardWorker extends \AGR\Models\Occupation
{
  public function __construct($row)
  {
    parent::__construct($row);
    $this->id = 'B140_FarmyardWorker';
    $this->name = clienttranslate('Farmyard Worker');
    $this->deck = 'B';
    $this->number = 140;
    $this->category = FOOD_PROVIDER;
    $this->desc = [
      clienttranslate('At the end of each work phase in which you placed at least 1 good on 1 of your farmyard spaces, you get 2 <FOOD>.'),
    ];
    $this->players = '3+';
    $this->isArtifexOrBubulcus = true;
    $this->rulings = [
      clienttranslate('You may not trigger this card by only moving an existing good.'),
    ];
  }

  public function isListeningTo($event)
  {
    return $this->isPlayerEvent($event) && $event['type'] == 'AfterWorkPhase';
  }

  public function onPlayerAfterWorkPhase($player, $event)
  {
    $flags = Globals::getFarmyardGoodsPlacedDuringWork();
    if (!empty($flags[$player->getId()])) {
      return $this->gainNode([FOOD => 2]);
    }
  }
}
