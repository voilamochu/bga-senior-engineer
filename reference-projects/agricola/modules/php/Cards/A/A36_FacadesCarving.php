<?php
namespace AGR\Cards\A;
use AGR\Core\Globals;

// TODO: better display of condition text?

class A36_FacadesCarving extends \AGR\Models\MinorImprovement
{
  protected $map = [
    1 => 0,
    2 => 0,
    3 => 0,
    4 => 0,
    5 => 1,
    6 => 1,
    7 => 1,
    8 => 2,
    9 => 2,
    10 => 3,
    11 => 3,
    12 => 4,
    13 => 4,
    14 => 5,
  ];    
    
  public function __construct($row)
  {
    parent::__construct($row);
    $this->id = 'A36_FacadesCarving';
    $this->name = clienttranslate('Facades Carving');
    $this->deck = 'A';
    $this->number = 36;
    $this->category = POINTS_PROVIDER;
    $this->desc = [
      clienttranslate(
        'When you play this card, you can exchange any number of <FOOD> for 1 bonus <SCORE> each, up to the number of completed harvests.'
      ),
    ];
    $this->cost = [
      CLAY => 2
    ];
    $this->extraVp = true;
    $this->prerequisite = clienttranslate('Wood in Your Supply >= Current Round');
    $this->isArtifexOrBubulcus = true;
    $this->bannedWeak1or2p = true;
  }

  public function isBuyable($player, $ignoreResources = false, $args = [])
  {
    $wood = $player->countReserveResource(WOOD);

    if ($wood < Globals::getTurn()) {
      return false;
    }
    return parent::isBuyable($player, $ignoreResources, $args);
  }
  
  public function onBuy($player) {
    $n = $this->map[Globals::getTurn()];
    
    $childs = [];
    for ($i = 1; $i <= $n; $i++) {
      $childs[] = $this->payGainNode([FOOD => $i], [SCORE => $i], null, false);
    }
    
    if ($n > 0) {
      return [
        'type' => NODE_XOR,
        'optional' => true,
        'doableRequiresChild' => true,
        'customDescription' => 'Pay <FOOD> for <SCORE>',
        'childs' => $childs,
      ];
    }
  }
}
