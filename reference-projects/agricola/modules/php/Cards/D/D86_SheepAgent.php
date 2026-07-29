<?php
namespace AGR\Cards\D;

class D86_SheepAgent extends \AGR\Models\Occupation
{
  public function __construct($row)
  {
    parent::__construct($row);
    $this->id = 'D86_SheepAgent';
    $this->name = clienttranslate('Sheep Agent');
    $this->deck = 'D';
    $this->number = 86;
    $this->category = FARM_PLANNER;
    $this->desc = [
      clienttranslate(
        'You can keep 1 <SHEEP> on this card for each occupation card in front of you (including this one), unless it is already able to hold animals.'
      ),
    ];

    $this->animalHolder=true;
    $this->players = '1+';
    $this->isCorbariusOrDulcinaria = true;
  }

  public function onPlayerComputeDropZones($player, &$args)
  {
    $capacity = $player->countOccupations();

    $animalHolderOccupations = ['A148_Woolgrower', 'B86_TruffleSearcher','B148_PetBroker','C86_LivestockFeeder'];
    foreach ($animalHolderOccupations as $animalHolderOccupation){
      if($player->hasPlayedCard($animalHolderOccupation)){

        $capacity = $capacity -1;
      }
    }

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