<?php
namespace AGR\Cards\E;

class E137_FlaxFarmer extends \AGR\Models\Occupation
{
  public function __construct($row)
  {
    parent::__construct($row);
    $this->id = 'E137_FlaxFarmer';
    $this->name = clienttranslate('Flax Farmer');
    $this->deck = 'E';
    $this->author = 'beso';
    $this->number = 137;
    $this->category = 'GOODS_-_GET';
    $this->desc = [
      clienttranslate(
        'Each time you use the __Reed Bank__ accumulation space, you also get 1 <GRAIN>. Each time you use the __Grain Seeds__ action space, you also get 1 <REED>.'
      ),
    ];
    $this->players = '3+';
  }

  public function isListeningTo($event)
  {
    return $this->isActionCardEvent($event, 'ReedBank') ||
	  $this->isActionCardEvent($event, 'GrainSeeds');
  }

  public function onPlayerPlaceFarmer($player, $event)
  {
    if ($this->isActionCardEvent($event, 'ReedBank')){
      return $this->gainNode([GRAIN => 1]);
    }
    else{
      return $this->gainNode([REED => 1]);
    }
  }
}