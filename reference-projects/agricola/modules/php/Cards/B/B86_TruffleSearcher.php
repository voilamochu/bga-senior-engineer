<?php
namespace AGR\Cards\B;
use AGR\Core\Globals;

class B86_TruffleSearcher extends \AGR\Models\Occupation
{
  public function __construct($row)
  {
    parent::__construct($row);
    $this->id = 'B86_TruffleSearcher';
    $this->name = clienttranslate('Truffle Searcher');
    $this->deck = 'B';
    $this->number = 86;
    $this->category = FARM_PLANNER;
    $this->desc = [
      clienttranslate(
        'This card can hold a number of <PIG> equal to the number of completed feeding phases.'
      ),
    ];
    $this->players = '1+';
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
        'constraints' => [PIG],
        'capacity' => $capacity,
        'locations' => [['type' => 'card', 'card_id' => $this->id]],
      ];
    }
  }
}
