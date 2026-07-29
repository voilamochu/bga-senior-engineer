<?php
namespace AGR\Cards\C;

class C11_WildlifeReserve extends \AGR\Models\MinorImprovement
{
  public function __construct($row)
  {
    parent::__construct($row);
    $this->id = 'C11_WildlifeReserve';
    $this->name = clienttranslate('Wildlife Reserve');
    $this->deck = 'C';
    $this->number = 11;
    $this->category = FARM_PLANNER;
    $this->desc = [clienttranslate('This card can hold up to 1 <SHEEP>, 1 <PIG>, and 1 <CATTLE>.')];
    $this->vp = 1;
    $this->cost = [
      WOOD => 2,
    ];
    $this->prerequisite = clienttranslate('2 Occupations');
    $this->occupationPrerequisites = ['min' => 2];
    $this->animalHolder = true;

    $this->rulings = [
      clienttranslate('This card does not count as a pasture.'),
    ];
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

  public function getInvalidAnimals($zone, $raiseException)
  {
    $counters = [
      SHEEP => 0,
      PIG => 0,
      CATTLE => 0
    ];

    $animals = [];

    foreach($zone['meeples'] as $meeple){
      $counters[$meeple['type']]++;

      if($counters[$meeple['type']] > 1){
        if ($raiseException) {
          throw new \feException('Too many animals for C11. Should not happen');
        }

        $animals[] = $meeple;
      }
    }

    return $animals;
  }

  public function canAcceptAnimal($zone, $meeple){
    return $zone[$meeple['type']] == 0;
  }
}
