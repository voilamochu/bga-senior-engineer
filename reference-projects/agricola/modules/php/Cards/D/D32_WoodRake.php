<?php
namespace AGR\Cards\D;
use AGR\Core\Globals;

class D32_WoodRake extends \AGR\Models\MinorImprovement
{
  public function __construct($row)
  {
    parent::__construct($row);
    $this->id = 'D32_WoodRake';
    $this->name = clienttranslate('Wood Rake');
    $this->deck = 'D';
    $this->number = 32;
    $this->category = POINTS_PROVIDER;
    $this->desc = [
      clienttranslate(
        'During scoring, if you had at least 7 goods in your fields before the final harvest, you get 2 bonus <SCORE>.'
      ),
    ];
    $this->cost = [
      WOOD => 1,
    ];
    $this->extraVp = true;
    $this->isCorbariusOrDulcinaria = true;
  }

  public function isListeningTo($event)
  {
    return $this->isPlayerEvent($event) && $event['type'] == 'BeforeHarvest' && Globals::getTurn() == 14;
  }

  public function onPlayerBeforeHarvest($player, $event)
  {
    $n = 0;

    foreach ($player->board()->getFieldsAndCrops() as $field) {
      $n += count($field['crops']);
      if ($n >= 7) {
        return $this->gainNode([SCORE => 2]);
      }
    }
  }
}
