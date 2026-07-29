<?php
namespace AGR\Cards\A;
use AGR\Managers\Players;
use AGR\Core\Engine;
use AGR\Core\Globals;
use AGR\Managers\Farmers;

class A162_ForestTallyman extends \AGR\Models\PlayerActionCard
{
  protected $type = OCCUPATION;
  public function __construct($row)
  {
    parent::__construct($row);
    $this->id = 'A162_ForestTallyman';
    $this->name = clienttranslate('Forest Tallyman');
    $this->deck = 'A';
    $this->number = 162;
    $this->category = BUILDING_RESOURCE_PROVIDER;
    $this->desc = [
      clienttranslate(
        'Each time both the __Forest__ and __Clay Pit__ accumulation spaces are occupied, you can use this card as an action space to get 2 <CLAY> and 3 <WOOD>.'
      ),
    ];
    $this->players = '4+';
    $this->isArtifexOrBubulcus = true;
    $this->privateSpace = true;
    $this->flow = [
      'action' => SPECIAL_EFFECT,
      'args' => [
        'method' => 'activate',
        'cardId' => $this->id,
        'args' => [],
      ],
    ];
  }

  public function canBePlayed($player, $onlyCheckSpecificPlayer = null, $ignoreResources = true, $ignoreDoable = false)
  {
    if ((!Farmers::getOnCard('ActionForest')->empty()) && (!Farmers::getOnCard('ActionClayPit')->empty())){
      return true && parent::canBePlayed($player, $onlyCheckSpecificPlayer, $ignoreResources);
    }
    return false; 
  }

  public function activate(){

    $flow = $this->gainNode([CLAY => 2,WOOD => 3], $this->getPlayer()->getId());
    Engine::insertAsChild($flow);
  }
}