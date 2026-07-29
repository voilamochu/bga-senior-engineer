<?php
namespace AGR\Cards\E;

class E84_DollysMother extends \AGR\Models\MinorImprovement
{
  public function __construct($row)
  {
    parent::__construct($row);
    $this->id = 'E84_DollysMother';
    $this->name = clienttranslate("Dolly's Mother");
    $this->deck = 'E';
    $this->author = 'azwandahlan';
    $this->number = 84;
    $this->category = 'ANIMALS_';
    $this->desc = [
      clienttranslate(
        'You only require 1 <SHEEP> to breed sheep during the breeding phase of a harvest. This card can hold 1 <SHEEP>.'
      ),
    ];
    $this->vp = 1;
    $this->prerequisite = clienttranslate('1 Sheep');
    $this->animalHolder = true;
    $this->implemented = true;
  }

  public function isBuyable($player, $ignoreResources = false, $args = [])
  {
    if ($player->countAnimalsOnBoard()[SHEEP] == 0) {
      return false;
    }

    return parent::isBuyable($player, $ignoreResources, $args);
  }

  public function onPlayerComputeDropZones($player, &$args)
  {  
    $args['zones'][] = [
      'type' => 'card',
      'card_id' => $this->id,
      'constraints' => [SHEEP],
      'capacity' => 1,
      'locations' => [['type' => 'card', 'card_id' => $this->id]],
    ];
  }

  // Rest of functionality in Models/Player.php -> breedTypes()
}
