<?php
namespace AGR\Cards\C;

class C141_SheepProvider extends \AGR\Models\Occupation
{
  public function __construct($row)
  {
    parent::__construct($row);
    $this->id = 'C141_SheepProvider';
    $this->name = clienttranslate('Sheep Provider');
    $this->deck = 'C';
    $this->number = 141;
    $this->category = CROP_PROVIDER;
    $this->desc = [
      clienttranslate(
        'Each time any player (including you) uses the __Sheep Market__ accumulation space, you get 1 <GRAIN>.'
      ),
    ];
    $this->players = '3+';
    $this->isCorbariusOrDulcinaria = true;
  }
  
  public function isListeningTo($event)
  {
    return $this->isActionCardEvent($event, 'SheepMarket', null);
  }
  
  public function onPlayerPlaceFarmer($player, $event)
  {
    return $this->gainNode([GRAIN => 1]);
  }  
  
  public function onOpponentPlaceFarmer($player, $event)
  {
    return $this->gainNode([GRAIN => 1]);
  }  
}
