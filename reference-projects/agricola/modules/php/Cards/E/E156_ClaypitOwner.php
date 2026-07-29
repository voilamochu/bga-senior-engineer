<?php
namespace AGR\Cards\E;
use AGR\Managers\PlayerCards;

class E156_ClaypitOwner extends \AGR\Models\Occupation
{
  public function __construct($row)
  {
    parent::__construct($row);
    $this->id = 'E156_ClaypitOwner';
    $this->name = clienttranslate('Claypit Owner');
    $this->deck = 'E';
    $this->author = 'inoshishi';
    $this->number = 156;
    $this->category = 'GOODS_-_GET';
    $this->desc = [
      clienttranslate(
        'Each time another player plays or builds an improvement with a printed <CLAY> cost, you get 1 <FOOD> and 1 <CLAY>.'
      ),
    ];
    $this->players = '4+';
  }

  public function isListeningTo($event)
  {
    return $this->isActionEvent($event, 'Improvement','opponent');
  }
  
  public function onOpponentAfterImprovement($player, $event)
  {
    $card = PlayerCards::get($event['cardId']);
    $costs = $card->getBaseCosts();

    foreach ($costs as $cost) {
      if (isset($cost[CLAY]) && $cost[CLAY] >= 1) {
        return $this->gainNode([FOOD => 1, CLAY => 1]);
      }
    }
  }
}
