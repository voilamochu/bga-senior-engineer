<?php

namespace AGR\Cards\D;

use AGR\Core\Notifications;
use AGR\Models\Occupation;
use AGR\Helpers\CardRulings;

class D148_DomesticianExpert extends Occupation
{
  public function __construct($row)
  {
    parent::__construct($row);
    $this->id = 'D148_DomesticianExpert';
    $this->name = clienttranslate('Domestician Expert');
    $this->deck = 'D';
    $this->number = 148;
    $this->isCorbariusOrDulcinaria = true;
    $this->category = FARM_PLANNER;
    $this->desc = [
      clienttranslate('You can keep 2 sheep on the border between each pair of orthogonally adjacent rooms.'),
    ];
    $this->players = '4+';

    $this->rulings = CardRulings::fromKeys([
      'NEGATED_BY_MILKING_PLACE',
    ]);
  }

  public function onBuy($player)
  {
    Notifications::updateDropZones($player);
  }

  public function onPlayerComputeDropZones($player, &$args)
  {
    if ($player->hasPlayedCard('D12_MilkingPlace')) return;

    $edges = $player->board()->getSurroundedByRoomsEdges();
    $newZone = [
      'type' => 'D148_special',
      'constraints' => [SHEEP],
      'capacity' => 0,
      'locations' => [],
    ];

    foreach ($edges as $edge) {
      $newZone['locations'][] = [
        'x' => $edge['x'],
        'y' => $edge['y'],
      ];
      $newZone['capacity'] += 2;
    }
    $args['zones'][] = $newZone;
  }
}
