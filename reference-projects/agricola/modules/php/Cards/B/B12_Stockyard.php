<?php
namespace AGR\Cards\B;

class B12_Stockyard extends \AGR\Models\MinorImprovement
{
  public function __construct($row)
  {
    parent::__construct($row);
    $this->id = 'B12_Stockyard';
    $this->name = clienttranslate('Stockyard');
    $this->deck = 'B';
    $this->number = 12;
    $this->category = FARM_PLANNER;
    $this->desc = [
      clienttranslate(
        'This card can hold up to 3 animals of the same type. (It is not considered a pasture).'
      ),
    ];
    $this->vp = 1;
    $this->cost = [
      WOOD => 1,
      STONE => 1,
    ];
    $this->animalHolder = true;
    $this->isArtifexOrBubulcus = true;
  }
  
  public function onPlayerComputeDropZones($player, &$args)
  {
    $args['zones'][] = [
      'type' => 'card',
      'card_id' => $this->id,
      'capacity' => 3,
      'locations' => [['type' => 'card', 'card_id' => $this->id]],
    ];
  }
}
