<?php
namespace AGR\Cards\B;
use AGR\Managers\Players;

class B121_Geologist extends \AGR\Models\Occupation
{
  public function __construct($row)
  {
    parent::__construct($row);
    $this->id = 'B121_Geologist';
    $this->name = clienttranslate('Geologist');
    $this->deck = 'B';
    $this->number = 121;
    $this->category = BUILDING_RESOURCE_PROVIDER;
    $this->desc = [
      clienttranslate(
        'Each time you use the __Forest__ or __Reed Bank__ accumulation space, you also get 1 <CLAY>. In games with 3 or more players, this also applies to the __Clay Pit__.'
      ),
    ];
    $this->players = '1+';
  }

  public function isListeningTo($event)
  {
    return $this->isActionCardEvent($event, 'Forest') ||
      $this->isActionCardEvent($event, 'ReedBank') ||
      ($this->isActionCardEvent($event, 'ClayPit') && Players::count() >= 3);
  }

  public function onPlayerPlaceFarmer($player, $event)
  {
    return $this->gainNode([CLAY => 1]);
  }
}
