<?php
namespace AGR\Cards\D;

class D105_Sculptor extends \AGR\Models\Occupation
{
  public function __construct($row)
  {
    parent::__construct($row);
    $this->id = 'D105_Sculptor';
    $this->name = clienttranslate('Sculptor');
    $this->deck = 'D';
    $this->number = 105;
    $this->category = GOODS_PROVIDER;
    $this->desc = [
      clienttranslate(
        'Each time you use a clay accumulation space, you also get 1 <FOOD>. Each time you use a stone accumulation space, you also get 1 <GRAIN>.'
      ),
    ];
    $this->players = '1+';
    $this->isCorbariusOrDulcinaria = true;
  }
  
  public function isListeningTo($event)
  {
    return $this->isBeforeCollectEvent($event, CLAY) ||
      $this->isBeforeCollectEvent($event, STONE);
  }

   public function onPlayerPlaceFarmer($player, $event)
  {
    $resource = $this->isBeforeCollectEvent($event, CLAY) ? FOOD : GRAIN;

    return $this->gainNode([$resource => 1]);
  } 
}
