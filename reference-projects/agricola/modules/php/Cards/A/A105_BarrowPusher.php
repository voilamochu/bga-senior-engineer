<?php
namespace AGR\Cards\A;

class A105_BarrowPusher extends \AGR\Models\Occupation
{
  public function __construct($row)
  {
    parent::__construct($row);
    $this->id = 'A105_BarrowPusher';
    $this->name = clienttranslate('Barrow Pusher');
    $this->deck = 'A';
    $this->number = 105;
    $this->category = GOODS_PROVIDER;
    $this->desc = [clienttranslate('For each new field tile you get, you also get 1 <CLAY> and 1 <FOOD>.')];
    $this->players = '1+';
    $this->isArtifexOrBubulcus = true;
  }
  
  public function isListeningTo($event)
  {
    return $this->isActionEvent($event, 'Plow');
  }

  public function onPlayerAfterPlow($player, $event)
  {
    return $this->gainNode([CLAY => 1, FOOD => 1]);
  }
}
