<?php
namespace AGR\Cards\A;
use AGR\Managers\Farmers;

class A35_SwimmingClass extends \AGR\Models\MinorImprovement
{
  public function __construct($row)
  {
    parent::__construct($row);
    $this->id = 'A35_SwimmingClass';
    $this->name = clienttranslate('Swimming Class');
    $this->deck = 'A';
    $this->number = 35;
    $this->category = POINTS_PROVIDER;
    $this->desc = [
      clienttranslate(
        'In the returning home phase of each round, if you return a person from the __Fishing__ accumulation space, you get 2 bonus <SCORE> for each newborn that you return home.'
      ),
    ];
    $this->cost = [
      FOOD => 1,
    ];
    $this->extraVp = true;
    $this->prerequisite = ('2 Occupations');
    $this->occupationPrerequisites = ['min' => 2];
    $this->isArtifexOrBubulcus = true;

    $this->rulings = [
      clienttranslate('If you used __Adoptive Parents__ (A092), there is no longer a newborn to return home.'),
    ];
  }

  public function isListeningTo($event)
  {
    return ($this->isPlayerEvent($event) && $event['type'] == 'StartReturnHome' &&
      !Farmers::getOnCard('ActionFishing', $this->getPlayer()->getId())->empty()) ||
      ($this->isPlayerEvent($event) && $event['type'] == 'StartOfTurn');
  }

  // using flags so D10 (Stork's Nest) knows there was a worker on fishing
  public function onPlayerStartOfTurn($player, $event)
  {
    return $this->unflagCardNode();
  }
  
  public function onPlayerStartReturnHome($player, $event)
  {
    $children = Farmers::getChildren($player->getId())->count();
    
    if ($children > 0) {
      return [
        'type' => NODE_SEQ,
        'childs' => [
          $this->flagCardNode(),
          $this->gainNode([SCORE => $children*2]),
        ]
      ];
    }

    else {
      return $this->flagCardNode();
    }
  }
}
