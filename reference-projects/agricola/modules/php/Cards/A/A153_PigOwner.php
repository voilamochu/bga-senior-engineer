<?php
namespace AGR\Cards\A;

class A153_PigOwner extends \AGR\Models\Occupation
{
  public function __construct($row)
  {
    parent::__construct($row);
    $this->id = 'A153_PigOwner';
    $this->name = clienttranslate('Pig Owner');
    $this->deck = 'A';
    $this->number = 153;
    $this->category = POINTS_PROVIDER;
    $this->desc = [
      clienttranslate(
        'The first time after you play this card that you have 5 <PIG> on your farm, you immediately get 3 bonus <SCORE>.'
      ),
    ];
    $this->players = '4+';
    $this->extraVp = true;
    $this->isArtifexOrBubulcus = true;
  }
  
  public function isListeningTo($event)
  {
    return $this->isAnytime($event) && !$this->isFlagged();
  }  
  
  public function onPlayerAtAnytime($player, $event)
  {
    if ($player->countAnimalsOnBoard()[PIG] >= 5) {
      return [
        'type' => NODE_SEQ,
        'childs' => [
          $this->flagCardNode(),
          $this->gainNode([SCORE => 3]),
        ]
      ];
    }
  }
  
  public function enforceReorganizeOnLastHarvest()
  {
    $this->getPlayer()->setPref(OPTION_SMART_REORGANIZE, OPTION_SMART_REORGANIZE_CONFIRM);

    $pigs = $this->getPlayer()->countAnimalsOnBoard()[PIG];
    return $pigs >= 5 && !$this->isFlagged();
  }  
}
