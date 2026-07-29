<?php
namespace AGR\Cards\E;
use AGR\Core\Globals;

class E140_Carter extends \AGR\Models\Occupation
{
  public function __construct($row)
  {
    parent::__construct($row);
    $this->id = 'E140_Carter';
    $this->name = clienttranslate('Carter');
    $this->deck = 'E';
    $this->author = 'oxmond';
    $this->number = 140;
    $this->category = FOOD;
    $this->desc = [
      clienttranslate(
        'Next round, each time you use a building resource accumulation space, you also get 1 <FOOD> for each building resource that you take from the space.'
      ),
    ];
    $this->players = '3+';
  }

  public function onBuy($player)
  {
    $this->setExtraDatas('triggerRound',Globals::getTurn()+1);
  }

  public function isListeningTo($event)
  {
    return $this->isCollectEvent($event) && Globals::getTurn()==$this->getExtraDatas('triggerRound');
  }

  public function onPlayerAfterCollect($player, $event)
  {
    $meeples = [WOOD => 0, CLAY => 0, REED => 0, STONE => 0];
    $foods =0;
    foreach ($event['meeples'] as $meeple) {
      $type = $meeple['type'];
      if (\array_key_exists($type, $meeples)) {
        $foods++;
      }
    }
    if ($foods > 0) {
      return $this->gainNode([FOOD => $foods]);
    }
  }
}
