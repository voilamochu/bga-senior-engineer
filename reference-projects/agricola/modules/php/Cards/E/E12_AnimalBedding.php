<?php
namespace AGR\Cards\E;

class E12_AnimalBedding extends \AGR\Models\MinorImprovement
{
  public function __construct($row)
  {
    parent::__construct($row);
    $this->id = 'E12_AnimalBedding';
    $this->name = clienttranslate('Animal Bedding');
    $this->deck = 'E';
    $this->author = 'keith';
    $this->number = 12;
    $this->category = 'FARMYARD_-_PLACE_FOR_ANIMALS';
    $this->desc = [
      clienttranslate(
        'You can keep 1 additional animal (of the same type) in each of your unfenced stables, and 2 additional animals (of the same type) in each pasture with stable.'
      ),
    ];
    $this->vp = 1;
    $this->prerequisite = clienttranslate('1 Grain Field');
    $this->implemented = true;
  }
  
  public function isBuyable($player, $ignoreResources = false, $args = [])
  {
    $grainFields = $player->board()->getGrainFields();
    if (count($grainFields) < 1) {
      return false;
    }

    return parent::isBuyable($player, $ignoreResources, $args);
  }
  
  protected function onBuy($player)
  {
    $this->refreshDropZones();
  }

  public function onPlayerComputeDropZones($player, &$args)
  {
    foreach ($args['zones'] as &$zone) {
      if ($zone['type'] == 'stable') {
        $zone['capacity'] += 1;
      }        
      elseif ($zone['type'] == 'pasture'&& count($zone['stables'])>0) {
        $zone['capacity'] += 2;
      }
    }
  }
}
