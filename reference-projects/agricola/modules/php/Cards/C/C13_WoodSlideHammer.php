<?php
namespace AGR\Cards\C;
use AGR\Helpers\Utils;

class C13_WoodSlideHammer extends \AGR\Models\MinorImprovement
{
  public function __construct($row)
  {
    parent::__construct($row);
    $this->id = 'C13_WoodSlideHammer';
    $this->name = clienttranslate('Wood Slide Hammer');
    $this->deck = 'C';
    $this->number = 13;
    $this->category = FARM_PLANNER;
    $this->desc = [
      clienttranslate(
        'On your first renovation, if you have at least 5 wood rooms, you can renovate to stone directly and you get a discount of 2 <STONE> on the renovation cost.'
      ),
    ];
    $this->cost = [
      WOOD => 1,
    ];
    $this->isCorbariusOrDulcinaria = true;
  }
  
  public function orderComputeCostsRenovation()
  {
    return [['>', 'B145_BrushwoodCollector'], ['<', 'A143_Stonecutter']];
  }
  
  public function onPlayerComputeCostsRenovation($player, &$args)
  {
    if ($player->getRoomType() == 'roomWood' && $player->countRooms() >= 5) {
      Utils::addBonus($args['costs'], [STONE => -2], $this->id);
    }
  }
}
