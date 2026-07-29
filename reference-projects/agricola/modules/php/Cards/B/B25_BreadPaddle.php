<?php
namespace AGR\Cards\B;

class B25_BreadPaddle extends \AGR\Models\MinorImprovement
{
  public function __construct($row)
  {
    parent::__construct($row);
    $this->id = 'B25_BreadPaddle';
    $this->name = clienttranslate('Bread Paddle');
    $this->deck = 'B';
    $this->number = 25;
    $this->category = ACTIONS_BOOSTER;
    $this->desc = [
      clienttranslate(
        'When you play this card, you immediately get 1 <FOOD>. For each occupation you play, you get an additional __Bake Bread__ action.'
      ),
    ];
    $this->cost = [
      WOOD => 1,
    ];
  }

  public function onBuy($player)
  {
    return $this->gainNode([FOOD => 1]);
  }

  public function isListeningTo($event)
  {
    return $this->isActionEvent($event, 'Occupation');
  }

  public function onPlayerAfterOccupation($player, $event)
  {
    return $this->bakeBreadNode();
  }
}
