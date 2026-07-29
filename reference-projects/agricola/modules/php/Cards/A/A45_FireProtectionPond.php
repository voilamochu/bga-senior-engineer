<?php
namespace AGR\Cards\A;
use AGR\Managers\Players;

class A45_FireProtectionPond extends \AGR\Models\MinorImprovement
{
  public function __construct($row)
  {
    parent::__construct($row);
    $this->id = 'A45_FireProtectionPond';
    $this->name = clienttranslate('Fire Protection Pond');
    $this->deck = 'A';
    $this->number = 45;
    $this->category = FOOD_PROVIDER;
    $this->desc = [
      clienttranslate(
        'Once you no longer live in a wooden house, place 1 <FOOD> on each of the next 6 round spaces. At the start of these rounds, you get the <FOOD>.'
      ),
    ];
    $this->cost = [
      FOOD => 1,
    ];
    $this->prerequisite = clienttranslate('Still in Wooden House');
    $this->isArtifexOrBubulcus = true;
  }
  
  public function isBuyable($player, $ignoreResources = false, $args = [])
  {
    if ($player->getRoomType() != 'roomWood') {
        return false;
    }
    return parent::isBuyable($player, $ignoreResources, $args);
  }
  
  public function isListeningTo($event)
  {
    return $this->isActionEvent($event, 'Renovation') && !$this->isFlagged();
  }

  public function onPlayerAfterRenovation($player, $event)
  {    
    return [
      'type' => NODE_SEQ,
      'childs' => [
        $this->futureMeeplesNode([FOOD => 1], 6),
        $this->flagCardNode(),
      ]
    ];
  }
}
