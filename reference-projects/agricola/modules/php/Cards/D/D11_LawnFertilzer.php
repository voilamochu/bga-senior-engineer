<?php
namespace AGR\Cards\D;

class D11_LawnFertilzer extends \AGR\Models\MinorImprovement
{
  public function __construct($row)
  {
    parent::__construct($row);
    $this->id = 'D11_LawnFertilzer';
    $this->name = clienttranslate('Lawn Fertilizer');
    $this->deck = 'D';
    $this->number = 11;
    $this->category = FARM_PLANNER;
    $this->desc = [
      clienttranslate(
        'Your pastures of size 1 can hold up to 3 animals of the same type. (With a stable, they can hold up to 6 animals of the same type.)'
      ),
    ];
    $this->isCorbariusOrDulcinaria = true;
    $this->implemented = false;
    // LEGACY FILE: typo in card name. see D11_LawnFertilizer.php for new card file.
  }

  public function onBuy($player)
  {
    $this->refreshDropZones();
  }
  
  public function onPlayerComputeDropZones($player, &$args)
  {
    foreach ($args['zones'] as &$zone) {
      if ($zone['type'] == 'pasture' && count($zone['locations']) == 1) {
        $zone['capacity'] = 3 * (count($zone['stables']) + 1);
        if ($player->hasPlayedCard('A12_DrinkingTrough')) {
          $zone['capacity'] += 2;
        }
      }
    }
  }
}
