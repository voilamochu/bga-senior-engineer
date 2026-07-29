<?php
namespace AGR\Cards\B;
use AGR\Managers\Meeples;
use AGR\Core\Notifications;

class B137_Wholesaler extends \AGR\Models\Occupation
{
  public function __construct($row)
  {
    parent::__construct($row);
    $this->id = 'B137_Wholesaler';
    $this->name = clienttranslate('Wholesaler');
    $this->deck = 'B';
    $this->number = 137;
    $this->category = GOODS_PROVIDER;
    $this->desc = [
      clienttranslate(
        'Place 1 <VEGETABLE>, 1 <PIG>, 1 <STONE>, and 1 <CATTLE> on this card. Each time you use an action space card on round spaces 8 to 11, you get the corresponding good from this card.'
      ),
    ];
    $this->players = '3+';
    $this->holder = true;
    $this->isArtifexOrBubulcus = true;
  }
  
  public function onBuy($player)
  {
    $types = [VEGETABLE, PIG, STONE, CATTLE];

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
    return (
      ($this->isActionCardEvent($event, 'VegetableSeeds') && !Meeples::getResourcesOnCard($this->id, null, VEGETABLE)->empty()) ||
      ($this->isActionCardEvent($event, 'PigMarket') && !Meeples::getResourcesOnCard($this->id, null, PIG)->empty()) ||
      ($this->isActionCardEvent($event, 'EasternQuarry') && !Meeples::getResourcesOnCard($this->id, null, STONE)->empty()) ||
      ($this->isActionCardEvent($event, 'CattleMarket') && !Meeples::getResourcesOnCard($this->id, null, CATTLE)->empty())
    );
  }
  
  public function onPlayerPlaceFarmer($player, $event)
  {
    $resource = [
      'VegetableSeeds' => VEGETABLE, 
      'PigMarket' => PIG, 
      'EasternQuarry' => STONE, 
      'CattleMarket' => CATTLE
    ];
    $cardId = $event['actionCardType'];
    $meeple = $this->getNextResource($resource[$cardId]);

    return $this->receiveNode($meeple['id'], true);
  }
}
