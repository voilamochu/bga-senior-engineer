<?php
namespace AGR\Cards\B;
use AGR\Helpers\CardRulings;

class B22_WalkingBoots extends \AGR\Models\MinorImprovement
{
  public function __construct($row)
  {
    parent::__construct($row);
    $this->id = 'B22_WalkingBoots';
    $this->name = clienttranslate('Walking Boots');
    $this->deck = 'B';
    $this->number = 22;
    $this->category = ACTIONS_BOOSTER;
    $this->desc = [
      clienttranslate(
        'You immediately get 2 <FOOD>. You must immediately place a person from your supply. If you do, in the next returning home phase, you must remove that person from play.'
      ),
    ];
    $this->prerequisite = clienttranslate('At Most 4 People');
    $this->isArtifexOrBubulcus = true;
    $this->bannedStrong3or4p = true;

    $this->rulings = CardRulings::fromKeys([
      'NEVER_FIVE_FARMERS',
    ]);
  }

  // TODO: fix playerboard display to decrease maximum rather than increase current counter when meeple is killed
  // also goes for cards like C54

  public function isBuyable($player, $ignoreResources = false, $args = [])
  {
    if ($player->countFarmers() == 5) {
      return false;
    }

    return parent::isBuyable($player, $ignoreResources, $args);
  }
  
  public function onBuy($player)
  {
    return [
      'type' => NODE_SEQ,
      'childs' => [
        $this->gainNode([FOOD => 2]),
        [
          'action' => PLACE_FARMER,
          'pId' => $player->getId(),
          'args' => [
            'fromSupply' => true,
            'source' => $this->id,
            'markForRemoval' => true,
          ]
        ]
      ]
    ];
  }
}
