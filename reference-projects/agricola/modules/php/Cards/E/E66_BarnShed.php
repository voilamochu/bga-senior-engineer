<?php
namespace AGR\Cards\E;

use AGR\Managers\Players;

class E66_BarnShed extends \AGR\Models\MinorImprovement
{
  public function __construct($row)
  {
    parent::__construct($row);
    $this->id = 'E66_BarnShed';
    $this->name = clienttranslate('Barn Shed');
    $this->deck = 'E';
    $this->author = 'inoshishi';
    $this->number = 66;
    $this->category = 'CROPS_-_GRAIN';
    $this->desc = [
      clienttranslate(
        'Each time another player (or, in a solo game, you) uses the __Forest__ accumulation space, you get 1 <GRAIN>.'
      ),
    ];
    $this->cost = [
      WOOD => 2,
    ];
    $this->prerequisite = clienttranslate('3 Occupations');
    $this->occupationPrerequisites = ['min' => 3];
  }

  public function isListeningTo($event)
  {
    return ($this->isActionEvent($event, 'PlaceFarmer', 'opponent') 
      && $event['actionCardType'] == 'Forest')||
      ((Players::count() == 1)&&$this->isActionCardEvent($event, 'Forest'));
  }

  public function onOpponentAfterPlaceFarmer($player, $event)
  {
    return $this->gainNode([GRAIN => 1]);
  }

  public function onPlayerPlaceFarmer($player, $event)
  {
    return $this->gainNode([GRAIN => 1]);
  }
}
