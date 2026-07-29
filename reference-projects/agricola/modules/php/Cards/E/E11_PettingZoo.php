<?php
namespace AGR\Cards\E;

class E11_PettingZoo extends \AGR\Models\MinorImprovement
{
  public function __construct($row)
  {
    parent::__construct($row);
    $this->id = 'E11_PettingZoo';
    $this->name = clienttranslate('Petting Zoo');
    $this->deck = 'E';
    $this->author = 'inoshishi';
    $this->number = 11;
    $this->category = 'FARMYARD_-_PLACE_FOR_ANIMALS';
    $this->desc = [
      clienttranslate(
        'As long as you have a pasture orthogonally adjacent to your house, you can keep animals of any type on this card, up to the number of rooms in your house.'
      ),
    ];
    $this->cost = [
      WOOD => 1,
    ];
    $this->animalHolder = true;
  }

  public function getInvalidAnimals($zone, $raiseException)
  {
    return [];
  }

  public function onPlayerComputeDropZones($player, &$args)
  {
    $adjacent = false;

    $rooms = $player->board()->getRooms();
    foreach ($rooms as $room) {
      if ($player->board()->isAdjacentToType($room['x'], $room['y'], 'pasture')) {
        $adjacent = true;
      }
    }

    if ($adjacent) {
      $args['zones'][] = [
        'type' => 'card',
        'card_id' => $this->id,
        'constraints' => [SHEEP, PIG, CATTLE],
        'capacity' => count($rooms),
        'locations' => [['type' => 'card', 'card_id' => $this->id]],
      ];
    }
  }  
}
