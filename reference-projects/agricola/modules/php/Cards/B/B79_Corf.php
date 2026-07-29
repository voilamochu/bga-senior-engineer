<?php
namespace AGR\Cards\B;

class B79_Corf extends \AGR\Models\MinorImprovement
{
  public function __construct($row)
  {
    parent::__construct($row);
    $this->id = 'B79_Corf';
    $this->name = clienttranslate('Corf');
    $this->deck = 'B';
    $this->number = 79;
    $this->category = BUILDING_RESOURCE_PROVIDER;
    $this->desc = [
      clienttranslate(
        'Each time any player (including you) takes at least 3 <STONE> from an accumulation space, you get 1 <STONE> from the general supply.'
      ),
    ];
    $this->cost = [
      REED => 1,
    ];
    $this->isArtifexOrBubulcus = true;
  }
  
  public function isListeningTo($event)
  {
    return $this->isCollectEvent($event, STONE, false, null);
  }

  public function onAfterCollect($player, $event)
  {
    $n = 0;

    foreach ($event['meeples'] as $meeple) {
      if ($meeple['type'] == STONE) {
        $n++;
      }
    }

    if ($n >= 3) {
      return $this->gainNode([STONE => 1]);
    }
  }

  public function onPlayerAfterCollect($player, $event)
  {
    return $this->onAfterCollect($player, $event);
  }  
  
  public function onOpponentAfterCollect($player, $event)
  {
    return $this->onAfterCollect($player, $event);
  }
}
