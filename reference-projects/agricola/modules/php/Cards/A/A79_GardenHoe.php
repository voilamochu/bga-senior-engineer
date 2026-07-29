<?php
namespace AGR\Cards\A;

class A79_GardenHoe extends \AGR\Models\MinorImprovement
{
  public function __construct($row)
  {
    parent::__construct($row);
    $this->id = 'A79_GardenHoe';
    $this->name = clienttranslate('Garden Hoe');
    $this->deck = 'A';
    $this->number = 79;
    $this->category = BUILDING_RESOURCE_PROVIDER;
    $this->desc = [
      clienttranslate(
        'Each time you take an unconditional __Sow__ action planting <VEGETABLE> in at least 1 field, you get 1 <CLAY> and 1 <STONE>.'
      ),
    ];
    $this->cost = [
      WOOD => 1,
    ];
    $this->isArtifexOrBubulcus = true;
  }

  public function isListeningTo($event)
  {
    return $this->isActionEvent($event, 'Sow') &&
      $event['unconditional'];
  }

  public function onPlayerAfterSow($player, $event)
  {
    foreach ($event['sows'] as $sow) {
      if ($sow['type'] == VEGETABLE) {
        return $this->gainNode([CLAY => 1, STONE => 1]);
      }
    }
  }
}
