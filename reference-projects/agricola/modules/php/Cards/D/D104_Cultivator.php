<?php
namespace AGR\Cards\D;

class D104_Cultivator extends \AGR\Models\Occupation
{
  public function __construct($row)
  {
    parent::__construct($row);
    $this->id = 'D104_Cultivator';
    $this->name = clienttranslate('Cultivator');
    $this->deck = 'D';
    $this->number = 104;
    $this->category = GOODS_PROVIDER;
    $this->desc = [clienttranslate('For each new field tile you get, you also get 1 <WOOD> and 1 <FOOD>.')];
    $this->players = '1+';
  }

  public function isListeningTo($event)
  {
    return $this->isActionEvent($event, 'Plow');
  }

  public function onPlayerAfterPlow($player, $event)
  {
    return $this->gainNode([WOOD => 1, FOOD => 1]);
  }
}
