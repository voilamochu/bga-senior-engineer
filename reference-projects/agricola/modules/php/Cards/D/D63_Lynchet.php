<?php
namespace AGR\Cards\D;

class D63_Lynchet extends \AGR\Models\MinorImprovement
{
  public function __construct($row)
  {
    parent::__construct($row);
    $this->id = 'D63_Lynchet';
    $this->name = clienttranslate('Lynchet');
    $this->deck = 'D';
    $this->number = 63;
    $this->category = FOOD_PROVIDER;
    $this->desc = [
      clienttranslate(
        'In the field phase of each harvest, you get 1 <FOOD> for each harvested field tile that is orthogonally adjacent to your house.'
      ),
    ];
    $this->isCorbariusOrDulcinaria = true;
  }

  public function isListeningTo($event)
  {
    return $this->isActionEvent($event, 'Reap') && (($event['trigger'] ?? null) == HARVEST) && $this->countFields($event) > 0;
  }

  public function countFields($event)
  {
    $player = $this->getPlayer();
    $fields = $player->board()->getHarvestedFieldTilePositions($event['crops'] ?? []);
    $n = 0;
 
    foreach ($fields as $field) {
      if ($player->board()->isAdjacentToType($field['x'], $field['y'], $player->getRoomType())) {
        $n++;
      }
    }
 
    return $n;
  }

  public function onPlayerAfterReap($player, $event)
  {
    $n = $this->countFields($event);
    return $this->gainNode([FOOD => $n]);
  }
}
