<?php
namespace AGR\Cards\C;

use AGR\Managers\PlayerCards;

class C137_CharcoalBurner extends \AGR\Models\Occupation
{
  public function __construct($row)
  {
    parent::__construct($row);
    $this->id = 'C137_CharcoalBurner';
    $this->name = clienttranslate('Charcoal Burner');
    $this->deck = 'C';
    $this->number = 137;
    $this->isCorbariusOrDulcinaria = true;
    $this->category = GOODS_PROVIDER;
    $this->desc = [
      clienttranslate(
        'Each time any player (including you) plays or builds a <BAKE>-improvement, you get 1 <WOOD> and 1 <FOOD>.'
      ),
    ];
    $this->players = '3+';
  }

  public function isListeningTo($event)
  {
    return $this->isActionEvent($event, 'Improvement', null);
  }

  public function onAfterImprovement($player, $event)
  {
    $card = PlayerCards::get($event['cardId']);
    if ($card->canBake()) {
      return $this->gainNode([WOOD => 1, FOOD => 1]);
    }
  }
}
