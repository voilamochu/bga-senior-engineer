<?php
namespace AGR\Cards\E;
use AGR\Managers\Meeples;
use AGR\Core\Notifications;

class E40_BeeStatue extends \AGR\Models\MinorImprovement
{
  public function __construct($row)
  {
    parent::__construct($row);
    $this->id = 'E40_BeeStatue';
    $this->name = clienttranslate('Bee Statue');
    $this->deck = 'E';
    $this->author = 'vancoch';
    $this->number = 40;
    $this->category = 'GOODS_-_GET';
    $this->desc = [
      clienttranslate(
        'Pile (from bottom to top) 1 <VEGETABLE>, 1 <STONE>, 1 <GRAIN>, 1 <STONE>, 1 <GRAIN> on this card. Each time you use the __Day Laborer__ action space, you get the top good.'
      ),
    ];
    $this->cost = [
      CLAY => 2,
    ];
    $this->holder = true;
  }

  public function onBuy($player)
  {
    $types = [VEGETABLE, STONE, GRAIN, STONE, GRAIN];

    $created = [];
    foreach ($types as $i => $type) {
      $created = array_merge(
        $created,
        Meeples::createResourceInLocation($type, $this->id, $player->getId(), null, null, 1, $i)
      );
    }

    Notifications::accumulate(Meeples::getMany($created), true);
  }

  public function isListeningTo($event)
  {
    return $this->isActionCardEvent($event, 'DayLaborer') && 
      $this->getNextResource() != null;
  }

  public function onPlayerPlaceFarmer($player, $event)
  {
    $meeple = $this->getNextResource();  
      
    return $this->receiveNode($meeple['id'], true);
  }
}
