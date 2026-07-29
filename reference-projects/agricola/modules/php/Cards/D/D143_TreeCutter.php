<?php
namespace AGR\Cards\D;

class D143_TreeCutter extends \AGR\Models\Occupation
{
  public function __construct($row)
  {
    parent::__construct($row);
    $this->id = 'D143_TreeCutter';
    $this->name = clienttranslate('Tree Cutter');
    $this->deck = 'D';
    $this->number = 143;
    $this->category = BUILDING_RESOURCE_PROVIDER;
    $this->desc = [
      clienttranslate(
        'Each time you use an accumulation space providing at least 3 goods of the same type except <WOOD>, you get an additional 1 <WOOD>. (<FOOD> is also considered a good.)'
      ),
    ];
    $this->players = '3+';
    $this->isCorbariusOrDulcinaria = true;
  }

  public function isListeningTo($event)
  {
    return $this->isCollectEvent($event) && $this->getGains($event);
  }

  public function getGains($event)
  {
    $meeples = [CLAY => 0, REED => 0, STONE => 0, FOOD => 0, SHEEP => 0, PIG => 0, CATTLE => 0, GRAIN => 0, VEGETABLE => 0];
    foreach ($event['meeples'] as $meeple) {
      $type = $meeple['type'];
      if (\array_key_exists($type, $meeples)) {
        $meeples[$type]++;
      }
    }

    foreach ($meeples as $type => $n) {
      if ($n >= 3) {
        return true;
      }
    }

    return false;
  }

  public function onPlayerAfterCollect($player, $event)
  {
    if ($this->getGains($event)) {
      return $this->gainNode([WOOD => 1]);
    }
  }
}
