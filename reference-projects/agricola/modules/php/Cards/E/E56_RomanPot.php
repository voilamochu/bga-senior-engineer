<?php
namespace AGR\Cards\E;
use AGR\Managers\Meeples;
use AGR\Managers\Players;
use AGR\Core\Notifications;

class E56_RomanPot extends \AGR\Models\MinorImprovement
{
  public function __construct($row)
  {
    parent::__construct($row);
    $this->id = 'E56_RomanPot';
    $this->name = clienttranslate('Roman Pot');
    $this->deck = 'E';
    $this->author = 'azwandahlan';
    $this->number = 56;
    $this->category = 'FOOD_-_FUTURE_ROUND_SPACES';
    $this->desc = [
      clienttranslate(
        'Place 4 <FOOD> from the general supply on this card. At the start of each work phase, if you are the last player in turn order, move 1 <FOOD> from this card to your supply.'
      ),
    ];
    $this->vp = 1;
    $this->cost = [
      CLAY => 1,
    ];
    $this->holder = true;
  }

  public function onBuy($player)
  {
    $created = Meeples::createResourceInLocation(FOOD, $this->id, $player->getId(), null, null, 4);
    Notifications::accumulate(Meeples::getMany($created), true);
  }
  
public function isListeningTo($event)
  {
    return $this->isPlayerEvent($event) && $event['type'] == 'startOfWork';
  }

  public function onPlayerStartOfWork($player, $event)
  {
    $order = Players::getTurnOrder();
    $meeple = $this->getNextResource(FOOD);

    if (end($order) == $player->getId() && !is_null($meeple)) {
      return $this->receiveNode($meeple['id'], true);
    }
  }
}
