<?php
namespace AGR\Cards\C;

class C147_Cowherd extends \AGR\Models\Occupation
{
  public function __construct($row)
  {
    parent::__construct($row);
    $this->id = 'C147_Cowherd';
    $this->name = clienttranslate('Cowherd');
    $this->deck = 'C';
    $this->number = 147;
    $this->category = LIVESTOCK_PROVIDER;
    $this->desc = [
      clienttranslate('Each time you use the __Cattle Market__ accumulation space, you get 1 additional <CATTLE>.'),
    ];
    $this->players = '3+';
  }

  public function isListeningTo($event)
  {
    return $this->isActionCardEvent($event, 'CattleMarket');
  }

  public function onPlayerPlaceFarmer($player, $event)
  {
    return $this->gainNode([CATTLE => 1]);
  }
}
