<?php
namespace AGR\Cards\B;
use AGR\Helpers\Utils;

class B2_MiniPasture extends \AGR\Models\MinorImprovement
{
  public function __construct($row)
  {
    parent::__construct($row);
    $this->id = 'B2_MiniPasture';
    $this->name = clienttranslate('Mini Pasture');
    $this->deck = 'B';
    $this->number = 2;
    $this->category = FARM_PLANNER;
    $this->desc = [
      clienttranslate(
        'Immediately fence a farmyard space, without paying <WOOD> for the fences. (If you already have pastures, the new one must be adjacent to an existing one.)'
      ),
    ];
    $this->passing = true;
    $this->cost = [
      FOOD => 2,
    ];
  }

  public function onBuy($player)
  {
    return [
      'action' => FENCING,
      'args' => ['miniPasture' => true, 'costs' => Utils::formatCost([WOOD => 0])],
    ];
  }
}
