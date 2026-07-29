<?php
namespace AGR\Cards\E;
use AGR\Helpers\CardRulings;

class E25_BumperCrop extends \AGR\Models\MinorImprovement
{
  public function __construct($row)
  {
    parent::__construct($row);
    $this->id = 'E25_BumperCrop';
    $this->name = clienttranslate('Bumper Crop');
    $this->deck = 'E';
    $this->author = 'ss';
    $this->number = 25;
    $this->category = 'ACTION';
    $this->desc = [
      clienttranslate(
        'When you play this card, immediately carry out the field phase on your farmyard only. (This is not a harvest.)'
      ),
    ];
    $this->vp = 1;
    $this->prerequisite = clienttranslate('2 Grain Fields');

    $this->rulings = CardRulings::fromKeys([
      'PRIVATE_FIELD_PHASE',
    ]);
  }
  
  public function isBuyable($player, $ignoreResources = false, $args = [])
  {
    $grainFields = $player->board()->getGrainFields();
    if (count($grainFields) < 2) {
      return false;
    }
    return parent::isBuyable($player, $ignoreResources, $args);
  }

  public function onBuy($player)
  {
    $crops = $player->board()->getHarvestCrops();
    if (count($crops) == 0) {
      return null;
    }

    return [
      'action' => REAP,
      'pId' => $player->getId(),
      'source' => $this->name,
      'args' => [
        'trigger' => PRIVATE_FIELD_PHASE,
      ],
    ];
  }

}
