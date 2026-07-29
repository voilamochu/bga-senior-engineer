<?php
namespace AGR\Cards\A;

class A155_Conjurer extends \AGR\Models\Occupation
{
  public function __construct($row)
  {
    parent::__construct($row);
    $this->id = 'A155_Conjurer';
    $this->name = clienttranslate('Conjurer');
    $this->deck = 'A';
    $this->number = 155;
    $this->category = GOODS_PROVIDER;
    $this->desc = [
      clienttranslate(
        'Each time you use the __Traveling Players__ accumulation space, you get an additional 1 <WOOD> and 1 <GRAIN>.'
      ),
    ];
    $this->players = '4+';
  }

  public function isListeningTo($event)
  {
    return $this->isActionCardEvent($event, 'TravelingPlayers');
  }

  public function onPlayerPlaceFarmer($player, $args)
  {
    return $this->gainNode([WOOD => 1, GRAIN => 1]);
  }
}
