<?php
namespace AGR\Cards\E;

class E79_FieldSpade extends \AGR\Models\MinorImprovement
{
  public function __construct($row)
  {
    parent::__construct($row);
    $this->id = 'E79_FieldSpade';
    $this->name = clienttranslate('Field Spade');
    $this->deck = 'E';
    $this->author = 'inoshishi';
    $this->number = 79;
    $this->category = 'BUILDING_RESOURCES_-_STONE';
    $this->desc = [clienttranslate('Each time after you sow in at least 1 field, you get 1 <STONE>.')];
    $this->cost = [
      WOOD => 1,
    ];
  }

  public function isListeningTo($event)
  {
    return $this->isActionEvent($event, 'Sow');
  }

  public function onPlayerAfterSow($player, $event)
  {
    return $this->gainNode([STONE => 1]);
  }
}
