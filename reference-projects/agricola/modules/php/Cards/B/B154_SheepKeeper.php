<?php
namespace AGR\Cards\B;

class B154_SheepKeeper extends \AGR\Models\Occupation
{
  public function __construct($row)
  {
    parent::__construct($row);
    $this->id = 'B154_SheepKeeper';
    $this->name = clienttranslate('Sheep Keeper');
    $this->deck = 'B';
    $this->number = 154;
    $this->category = POINTS_PROVIDER;
    $this->desc = [
      clienttranslate(
        'You can only play this card if you have less than 7 <SHEEP>. Once this game, when you have 7 <SHEEP> on your farm, you immediately get 3 bonus <SCORE> and 2 <FOOD>.'
      ),
    ];
    $this->players = '4+';
    $this->extraVp = true;
    $this->isArtifexOrBubulcus = true;
  }

  public function isBuyable($player, $ignoreResources = false, $args = [])
  {
    if ($player->countAnimalsOnBoard()[SHEEP] >= 7) {
      return false;
    }

    return parent::isBuyable($player, $ignoreResources, $args);
  }

  public function isListeningTo($event)
  {
    $player = $this->getPlayer();

    return $this->isAnytime($event) && !$this->isFlagged();
  }

  public function onPlayerAtAnytime($player, $event)
  {
    if ($player->countAnimalsOnBoard()[SHEEP] >= 7) {
      return [
        'type' => NODE_SEQ,
        'childs' => [
          $this->flagCardNode(),
          $this->gainNode([FOOD => 2, SCORE => 3]),
        ]
      ];
    }
  }
  
  public function enforceReorganizeOnLastHarvest()
  {
    $this->getPlayer()->setPref(OPTION_SMART_REORGANIZE, OPTION_SMART_REORGANIZE_CONFIRM);

    $sheep = $this->getPlayer()->countAnimalsOnBoard()[SHEEP];
    return $sheep >= 7 && !$this->isFlagged();
  }
}
