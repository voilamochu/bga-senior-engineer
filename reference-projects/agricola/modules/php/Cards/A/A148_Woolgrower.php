<?php
namespace AGR\Cards\A;
use AGR\Core\Globals;

class A148_Woolgrower extends \AGR\Models\Occupation
{
  public function __construct($row)
  {
    parent::__construct($row);
    $this->id = 'A148_Woolgrower';
    $this->name = clienttranslate('Woolgrower');
    $this->deck = 'A';
    $this->number = 148;
    $this->category = FARM_PLANNER;
    $this->desc = [
      clienttranslate('This card can hold a number of <SHEEP> equal to the number of completed feeding phases.'),
    ];
    $this->players = '4+';
    $this->animalHolder = true;
    $this->isArtifexOrBubulcus = true;
  }
  
  public function onPlayerComputeDropZones($player, &$args)
  {
    $capacity = Globals::getCompletedFeedingPhases();
    
    if ($capacity > 0) {
      $args['zones'][] = [
        'type' => 'card',
        'card_id' => $this->id,
        'constraints' => [SHEEP],
        'capacity' => $capacity,
        'locations' => [['type' => 'card', 'card_id' => $this->id]],
      ];
    }
  }
}
