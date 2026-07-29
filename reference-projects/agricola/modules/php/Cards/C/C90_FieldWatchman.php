<?php
namespace AGR\Cards\C;


class C90_FieldWatchman extends \AGR\Models\Occupation
{
  public function __construct($row)
  {
    parent::__construct($row);
    $this->id = 'C90_FieldWatchman';
    $this->name = clienttranslate('Field Watchman');
    $this->deck = 'C';
    $this->number = 90;
    $this->category = FARM_PLANNER;
    $this->desc = [clienttranslate('Each time you use the __Grain Seeds__ action space, you can also plow 1 field.')];
    $this->players = '1+';
  }

  public function isListeningTo($event)
  {
    return $this->isActionCardEvent($event, 'GrainSeeds');
  }

  public function onPlayerPlaceFarmer($player, $event)
  {
    return [
      'action' => PLOW,
      'cardId' => $this->id,
      'optional' => true,
      'source' => $this->name,
    ];
  }
}
