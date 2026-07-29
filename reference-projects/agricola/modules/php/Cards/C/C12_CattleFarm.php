<?php
namespace AGR\Cards\C;

class C12_CattleFarm extends \AGR\Models\MinorImprovement
{
  public function __construct($row)
  {
    parent::__construct($row);
    $this->id = 'C12_CattleFarm';
    $this->name = clienttranslate('Cattle Farm');
    $this->deck = 'C';
    $this->number = 12;
    $this->category = FARM_PLANNER;
    $this->desc = [clienttranslate('For each pasture you have, you can keep 1 <CATTLE> on this card.')];
    $this->cost = [
      WOOD => 1,
    ];
    $this->animalHolder = true;
    $this->isCorbariusOrDulcinaria = true;
    $this->bannedWeak1or2p = true;
    $this->bannedWeak3or4p = true;
  }
  
  public function onPlayerComputeDropZones($player, &$args)
  {
    $pastures = count($player->board()->getPastures(true));
    
    if ($pastures > 0) {
      $args['zones'][] = [
        'type' => 'card',
        'card_id' => $this->id,
        'constraints' => [CATTLE],
        'capacity' => $pastures,
        'locations' => [['type' => 'card', 'card_id' => $this->id]],
      ];
    }
  }

  public function getInvalidAnimals($zone, $raiseException)
  {
    $counter = 0;
    $player = $this->getPlayer();
    $pastures = count($player->board()->getPastures(true));
    $animals = [];

    foreach ($zone['meeples'] as $meeple) {
      $counter++;

      if ($counter > $pastures) {
        if ($raiseException) {
          throw new \feException('Too many animals for C12. Should not happen');
        }

        $animals[] = $meeple;
      }
    }

    return $animals;
  }
}
