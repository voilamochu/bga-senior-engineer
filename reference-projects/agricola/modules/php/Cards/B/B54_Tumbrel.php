<?php
namespace AGR\Cards\B;

class B54_Tumbrel extends \AGR\Models\MinorImprovement
{
  public function __construct($row)
  {
    parent::__construct($row);
    $this->id = 'B54_Tumbrel';
    $this->name = clienttranslate('Tumbrel');
    $this->deck = 'B';
    $this->number = 54;
    $this->category = FOOD_PROVIDER;
    $this->desc = [
      clienttranslate(
        'When you play this card, you immediately get 2 <FOOD>. Each time after you take an unconditional __Sow__ action, you get 1 <FOOD> for each stable you have.'
      ),
    ];
    $this->cost = [
      WOOD => 1,
    ];
    $this->isArtifexOrBubulcus = true;
  }

  public function onBuy($player)
  {
    return $this->gainNode([FOOD => 2]);
  }

  public function isListeningTo($event)
  {
    return $this->isActionEvent($event, 'Sow') &&
      $event['unconditional'];
  }

  public function onPlayerAfterSow($player, $event)
  {
    $stables = $player->board()->countStablesForCards();
    if ($stables > 0) {
      return $this->gainNode([FOOD => $stables]);
    }
  }
}
