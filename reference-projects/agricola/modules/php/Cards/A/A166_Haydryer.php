<?php
namespace AGR\Cards\A;

class A166_Haydryer extends \AGR\Models\Occupation
{
  public function __construct($row)
  {
    parent::__construct($row);
    $this->id = 'A166_Haydryer';
    $this->name = clienttranslate('Haydryer');
    $this->deck = 'A';
    $this->number = 166;
    $this->category = LIVESTOCK_PROVIDER;
    $this->desc = [
      clienttranslate(
        'Immediately before each harvest, you can buy 1 <CATTLE> for 4 <FOOD> minus 1 <FOOD> for each pasture you have. (The minimum cost is 0).'
      ),
    ];
    $this->players = '4+';
    $this->isArtifexOrBubulcus = true;
  }
  
  public function isListeningTo($event)
  {
    return $this->isPlayerEvent($event) && $event['type'] == 'BeforeHarvest';
  }

  public function onPlayerBeforeHarvest($player, $event)
  {
    $pastures = count($player->board()->getPastures(true));
    $cost = 4 - $pastures;
    
    if ($cost <= 0) {
      return $this->gainNode([CATTLE => 1]);
    }
    
    return $this->payGainNode([FOOD => $cost], [CATTLE => 1]);
  }
}
