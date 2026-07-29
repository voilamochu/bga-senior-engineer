<?php
namespace AGR\Cards\B;
use AGR\Managers\Players;

class B35_HookKnife extends \AGR\Models\MinorImprovement
{
  public function __construct($row)
  {
    parent::__construct($row);
    $this->id = 'B35_HookKnife';
    $this->name = clienttranslate('Hook Knife');
    $this->deck = 'B';
    $this->number = 35;
    $this->category = POINTS_PROVIDER;
    $this->desc = [
      clienttranslate(
        'Once this game, when you have 9/8/7/6/5/5 <SHEEP> on your farm in a 1-/2-/3-/4-/5-/6- player game, you immediately get 2 bonus <SCORE>.'
      ),
    ];
    $this->cost = [
      WOOD => 1,
    ];
    $this->extraVp = true;
    $this->isArtifexOrBubulcus = true;
  }
  
  public function isListeningTo($event)
  {
    return $this->isAnytime($event) && !$this->isFlagged();
  }
  
  public function onPlayerAtAnytime($player, $event)
  {
    $requiredSheep = [9, 9, 8, 7, 6, 5, 5];
    $playerCount = Players::count();
    
    if ($player->countAnimalsOnBoard()[SHEEP] >= $requiredSheep[$playerCount]) {
      return [
        'type' => NODE_SEQ,
        'childs' => [
          $this->flagCardNode(),
          $this->gainNode([SCORE => 2]),
        ]
      ];
    }
  }
  
  public function enforceReorganizeOnLastHarvest()
  {
    $this->getPlayer()->setPref(OPTION_SMART_REORGANIZE, OPTION_SMART_REORGANIZE_CONFIRM);

    $requiredSheep = [9, 9, 8, 7, 6, 5, 5];
    $playerCount = Players::count();

    $sheep = $this->getPlayer()->countAnimalsOnBoard()[SHEEP];
    return $sheep >= $requiredSheep[$playerCount] && !$this->isFlagged();
  }
}
