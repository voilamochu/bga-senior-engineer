<?php
namespace AGR\Cards\C;

class C68_Bookcase extends \AGR\Models\MinorImprovement
{
  public function __construct($row)
  {
    parent::__construct($row);
    $this->id = 'C68_Bookcase';
    $this->name = clienttranslate('Bookcase');
    $this->deck = 'C';
    $this->number = 68;
    $this->category = CROP_PROVIDER;
    $this->desc = [clienttranslate('Each time after you play an occupation, you get 1 <VEGETABLE>.')];
    $this->cost = [
      WOOD => 2,
    ];
    $this->prerequisite = clienttranslate('1 Occupation');
    $this->occupationPrerequisites = ['min' => 1];
  }

  public function isListeningTo($event)
  {
    return $this->isActionEvent($event, 'Occupation');
  }

  public function onPlayerAfterOccupation($player, $event)
  {
    return $this->gainNode([VEGETABLE => 1]);
  }
}
