<?php
namespace AGR\Cards\B;

class B61_ThreeFieldRotation extends \AGR\Models\MinorImprovement
{
  public function __construct($row)
  {
    parent::__construct($row);
    $this->id = 'B61_ThreeFieldRotation';
    $this->name = clienttranslate('Three-Field Rotation');
    $this->deck = 'B';
    $this->number = 61;
    $this->category = FOOD_PROVIDER;
    $this->desc = [
      clienttranslate(
        'At the start of the field phase of each harvest, if you have at least 1 <GRAIN> field, 1 <VEGETABLE> field, and 1 empty field, you get 3 <FOOD>.'
      ),
    ];
    $this->prerequisite = clienttranslate('3 Occupations');
    $this->occupationPrerequisites = ['min' => 3];
  }

  public function isListeningTo($event)
  {
    return $this->isPlayerEvent($event) && $event['type'] == 'StartHarvestFieldPhase' && $this->checkConditions();
  }

  public function onPlayerStartHarvestFieldPhase($player, $event)
  {
    return $this->gainNode([FOOD => 3]);
  }

  public function checkConditions()
  {
    $player = $this->getPlayer();
    $vegetable = false;
    $grain = false;
    $empty = $player->board()->countEmptyLogicalFields() > 0;

    foreach ($player->board()->getFieldsAndCrops() as $field) {
      $grain = $grain || $field['fieldType'] == GRAIN;
      $vegetable = $vegetable || $field['fieldType'] == VEGETABLE;
    }

    return $grain && $vegetable && $empty;
  }
}
