<?php
namespace AGR\Cards\E;

class E111_Recluse extends \AGR\Models\Occupation
{
  public function __construct($row)
  {
    parent::__construct($row);
    $this->id = 'E111_Recluse';
    $this->name = clienttranslate('Recluse');
    $this->deck = 'E';
    $this->author = 'letsdance';
    $this->number = 111;
    $this->category = FOOD;
    $this->desc = [
      clienttranslate(
        'As long as you have no minor improvements in front of you, you get 1 <FOOD> at the start of each round and 1 <WOOD> at the start of each harvest.'
      ),
    ];
    $this->players = '1+';
  }

  public function isListeningTo($event)
  {
    $player = $this->getPlayer();
    $minorCount = $player->getCards(MINOR, true)->count() - $player->getMajorMinor()->count();
    return $minorCount == 0 && $this->isPlayerEvent($event) && ($event['type'] == 'StartHarvest' || $event['type'] == 'StartOfTurn');
  }

  public function onPlayerStartOfTurn($player, $event)
  {
    return $this->gainNode([FOOD => 1]);
  }

  public function onPlayerStartHarvest($player, $event)
  {
    return $this->gainNode([WOOD => 1]);
  }
}
