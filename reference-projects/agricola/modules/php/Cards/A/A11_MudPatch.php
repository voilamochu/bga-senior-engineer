<?php

namespace AGR\Cards\A;

use AGR\Core\Notifications;
use AGR\Helpers\Utils;
use AGR\Models\MinorImprovement;

class A11_MudPatch extends MinorImprovement
{
  public function __construct($row)
  {
    parent::__construct($row);
    $this->id = 'A11_MudPatch';
    $this->name = clienttranslate('Mud Patch');
    $this->deck = 'A';
    $this->number = 11;
    $this->category = FARM_PLANNER;
    $this->desc = [
      clienttranslate(
        'When you play this card, you immediately get 1 <PIG>. You can hold 1 <PIG> on each of your unplanted field tiles.'
      ),
    ];
    $this->isArtifexOrBubulcus = true;
  }

  public function onBuy($player)
  {
    Notifications::updateDropZones($player);
    return $this->gainNode([PIG => 1]);
  }

  public function onPlayerComputeDropZones($player, &$args)
  {
    $sowableFields = $player->board()->getEmptyFields();

    // Get only field tiles
    Utils::filter($sowableFields, function ($field) {
      return ($field['location'] ?? null) == 'board' && ($field['type'] ?? null) == 'field';
    });

    $capacity = count($sowableFields);

    if ($capacity > 0) {
      $fields = $sowableFields;
      $newZone = [
        'type' => 'A11_special',
        'constraints' => [PIG],
        'capacity' => $capacity,
        'locations' => [],
      ];

      foreach ($fields as $field) {
        $newZone['locations'][] = [
          'x' => $field['x'],
          'y' => $field['y'],
        ];
      }

      $args['zones'][] = $newZone;
    }
  }
}
