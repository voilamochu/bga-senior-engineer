<?php
namespace AGR\Cards\E;

class E50_WildGreens extends \AGR\Models\MinorImprovement
{
  public function __construct($row)
  {
    parent::__construct($row);
    $this->id = 'E50_WildGreens';
    $this->name = clienttranslate('Wild Greens');
    $this->deck = 'E';
    $this->author = 'luki';
    $this->number = 50;
    $this->category = FOOD;
    $this->desc = [clienttranslate('Each time you sow, you get 1 <FOOD> for every different type of good that you sow.')];
    $this->implemented = true;
  }
  public function isListeningTo($event)
  {
    return $this->isActionEvent($event, 'Sow');      
  }

  public function onPlayerAfterSow($player, $event)
  {
    $grain = 0;
    $vegetable = 0;
    $wood = 0;
    $stone = 0;
    foreach ($event['sows'] as $sow) {
      if ($sow['type'] == GRAIN) {
        $grain = 1;
      }
      elseif ($sow['type'] == VEGETABLE) {
        $vegetable = 1;
      }
      elseif ($sow['type'] == WOOD) {
        $wood = 1;
      }
      elseif ($sow['type'] == STONE) {
        $stone = 1;
      }
    }
    $totals=$grain + $vegetable + $wood + $stone;
    if ($totals>0)
    return $this->gainNode([FOOD => $totals]);
  }
}
