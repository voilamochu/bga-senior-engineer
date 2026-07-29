<?php
namespace AGR\Cards\D;
use AGR\Managers\Meeples;
use AGR\Managers\PlayerCards;
use AGR\Core\Notifications;
use AGR\Core\Stats;

class D118_Bonehead extends \AGR\Models\Occupation
{
  public function __construct($row)
  {
    parent::__construct($row);
    $this->id = 'D118_Bonehead';
    $this->name = clienttranslate('Bonehead');
    $this->deck = 'D';
    $this->number = 118;
    $this->category = BUILDING_RESOURCE_PROVIDER;
    $this->desc = [
      clienttranslate(
        'When you play this card, immediately place 6 <WOOD> on it. Immediately after each time you play a card from your hand, including this one, you get 1 <WOOD> from this card.'
      ),
    ];
    $this->players = '1+';
    $this->holder = true;
  }

  public function onBuy($player)
  {
    $created = Meeples::createResourceInLocation(WOOD, $this->id, $player->getId(), null, null, 6);
    Notifications::accumulate(Meeples::getMany($created), true);
  }

  public function isListeningTo($event)
  {
    return $this->getNextResource() != null &&
      ($this->isActionEvent($event, 'Occupation') ||
        ($this->isActionEvent($event, 'Improvement') && PlayerCards::get($event['cardId'])->getType() == MINOR));
  }

  public function onPlayerAfterOccupation($player, $event)
  {
    $meeple = $this->getNextResource();
      
    // Needed if two cards are played in a row because of Merchant !
    if (is_null($meeple)) {
      return null;
    }

    return $this->receiveNode($meeple['id'], true);
  }

  public function onPlayerAfterImprovement($player, $event)
  {
    $meeple = $this->getNextResource();
      
    // Needed if two cards are played in a row because of Merchant !
    if (is_null($meeple)) {
      return null;
    }

    return $this->receiveNode($meeple['id'], true);
  }
}
