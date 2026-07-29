<?php
namespace AGR\Cards\E;

class E114_ShedBuilder extends \AGR\Models\Occupation
{
  public function __construct($row)
  {
    parent::__construct($row);
    $this->id = 'E114_ShedBuilder';
    $this->name = clienttranslate('Shed Builder');
    $this->deck = 'E';
    $this->author = 'nwoll';
    $this->number = 114;
    $this->category = 'CROPS_-_GRAIN_AND_VEGETABLE';
    $this->desc = [
      clienttranslate(
        'When you build your 1st and 2nd stable, you get 1 <GRAIN>. When you build your 3rd and 4th stable, you get 1 <VEGETABLE>. (This does not apply to stables you have already built.)'
      ),
    ];
    $this->players = '1+';
  }

  public function isListeningTo($event)
  {
    return $this->isActionEvent($event, 'Stables');
  }

  public function onPlayerAfterStables($player, $event)
  {
    
    $nAfter = $player->board()->countStablesForCards();
    $n = count($event['stables']) + (!empty($event['farmHandStableBuilt']) ? 1 : 0);
    $nBefore = $nAfter - $n;

    $gains = [GRAIN => 0, VEGETABLE => 0];
    for ($i = $nBefore + 1; $i <= $nAfter; $i++) {
      if ($i == 1 || $i == 2) {
        $gains[GRAIN] = $gains[GRAIN] + 1;
      }
      if ($i == 3 || $i == 4) {
        $gains[VEGETABLE] = $gains[VEGETABLE] + 1;
      }
    }

    if($gains[GRAIN] == 0){
      unset($gains[GRAIN]);
    }
    if($gains[VEGETABLE] == 0){
      unset($gains[VEGETABLE]);
    }

    if ($gains != []) {
      return $this->gainNode($gains);
    }
  }
}
