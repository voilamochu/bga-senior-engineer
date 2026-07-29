<?php
namespace AGR\Cards\C;

use AGR\Core\Notifications;
use AGR\Helpers\Utils;

class C89_StableMaster extends \AGR\Models\Occupation
{
  public function __construct($row)
  {
    parent::__construct($row);
    $this->id = 'C89_StableMaster';
    $this->name = clienttranslate('Stable Master');
    $this->deck = 'C';
    $this->number = 89;
    $this->isCorbariusOrDulcinaria = true;
    $this->category = FARM_PLANNER;
    $this->desc = [
      clienttranslate(
        'When you play this card, you can immediately build exactly 1 stable for 1 <WOOD>. Exactly one of your unfenced stables can hold up to 3 animals of one type.'
      ),
    ];
    $this->players = '1+';
  }

  public function onBuy($player)
  {
    Notifications::updateDropZones($player);
    if ($player->countStablesInReserve() > 0) {
      return [
        'action' => \STABLES,
        'optional' => true,
        'args' => [
          'max' => 1,
          'costs' => Utils::formatCost([WOOD => 1]),
        ],
      ];
    }
  }

  public function onPlayerComputeDropZones($player, &$args)
  {
    foreach ($args['zones'] as &$zone) {
      if ($zone['type'] == 'stable') {
        $zone['capacity'] += 2;
        return;
      }
    }
  }
}
