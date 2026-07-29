<?php
namespace AGR\Cards\B;

class B148_PetBroker extends \AGR\Models\Occupation
{
  public function __construct($row)
  {
    parent::__construct($row);
    $this->id = 'B148_PetBroker';
    $this->name = clienttranslate('Pet Broker');
    $this->deck = 'B';
    $this->number = 148;
    $this->category = FARM_PLANNER;
    $this->desc = [
      clienttranslate(
        'When you play this card, you immediately get 1 <SHEEP>. You can keep 1 <SHEEP> on this card for each occupation in front of you.'
      ),
    ];
    $this->players = '4+';
    $this->animalHolder = true;    
    $this->isArtifexOrBubulcus = true;
  }

  public function onBuy($player) 
  {
    return $this->gainNode([SHEEP => 1]);
  }

  public function onPlayerComputeDropZones($player, &$args)
  {
    $capacity = $player->countOccupations();

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
