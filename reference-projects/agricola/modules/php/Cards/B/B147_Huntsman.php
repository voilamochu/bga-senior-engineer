<?php
namespace AGR\Cards\B;

class B147_Huntsman extends \AGR\Models\Occupation
{
  public function __construct($row)
  {
    parent::__construct($row);
    $this->id = 'B147_Huntsman';
    $this->name = clienttranslate('Huntsman');
    $this->deck = 'B';
    $this->number = 147;
    $this->category = LIVESTOCK_PROVIDER;
    $this->desc = [
      clienttranslate('Each time after you use a wood accumulation space, you can pay 1 <GRAIN> to get 1 <PIG>.'),
    ];
    $this->players = '3+';
    $this->isArtifexOrBubulcus = true;
    $this->bannedWeak3or4p = true;
  }
  
  public function isListeningTo($event)
  {
    return $this->isCollectEvent($event, WOOD);
  }

  public function onPlayerAfterCollect($player, $event)
  {
    return $this->payGainNode([GRAIN => 1],[PIG => 1]);
  }
}
