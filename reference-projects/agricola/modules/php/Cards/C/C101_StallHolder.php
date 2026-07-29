<?php
namespace AGR\Cards\C;
use AGR\Helpers\Utils;

class C101_StallHolder extends \AGR\Models\Occupation
{
  public function __construct($row)
  {
    parent::__construct($row);
    $this->id = 'C101_StallHolder';
    $this->name = clienttranslate('Stall Holder');
    $this->deck = 'C';
    $this->number = 101;
    $this->category = POINTS_PROVIDER;
    $this->desc = [
      clienttranslate(
        'Once per round, if you have 0/1/2/3/4 unfenced stables on your farm, you can exchange 2 <GRAIN> for 1 bonus <SCORE> and 1/2/3/4/5 <FOOD>.'
      ),
    ];
    $this->players = '1+';
    $this->extraVp = true;
    $this->isCorbariusOrDulcinaria = true;
  }
  
  public function isListeningTo($event)
  {
    return ($this->isAnytime($event) && !$this->isFlagged()) || // Use the card
      ($this->isPlayerEvent($event) && $event['type'] == 'StartOfTurn'); // Unuse the card
  }

  public function onPlayerAtAnytime($player, $event)
  {
    $stables = $player->board()->countUnfencedStablesForCards();
    return [
      'type' => NODE_SEQ,
      'countAsUse' => true,
      'childs' => [
        $this->flagCardNode(),
        $this->payGainNode([GRAIN => 2], [SCORE => 1, FOOD => $stables+1], null, false),
      ],
    ];
  }

  public function onPlayerStartOfTurn($player, $event)
  {
    return $this->unflagCardNode();
  }
}
