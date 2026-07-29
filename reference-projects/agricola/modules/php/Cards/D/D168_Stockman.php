<?php
namespace AGR\Cards\D;

class D168_Stockman extends \AGR\Models\Occupation
{
  public function __construct($row)
  {
    parent::__construct($row);
    $this->id = 'D168_Stockman';
    $this->name = clienttranslate('Stockman');
    $this->deck = 'D';
    $this->number = 168;
    $this->category = LIVESTOCK_PROVIDER;
    $this->desc = [
      clienttranslate(
        'When you build your 2nd/3rd/4th stable, you immediately get 1 <CATTLE>/<PIG>/<SHEEP>, even if built on the same turn (but not retroactively).'
      ),
    ];
    $this->players = '4+';
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
    $gains = [];
    for ($i = $nBefore + 1; $i <= $nAfter; $i++) {
      if ($i == 2) {
        $gains[CATTLE] = 1;
      }
      if ($i == 3) {
        $gains[PIG] = 1;
      }
      if ($i == 4) {
        $gains[SHEEP] = 1;
      }
    }

    if ($gains != []) {
      return $this->gainNode($gains);
    }
  }
}
