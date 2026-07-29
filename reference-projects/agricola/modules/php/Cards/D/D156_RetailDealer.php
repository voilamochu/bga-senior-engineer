<?php
namespace AGR\Cards\D;
use AGR\Managers\Meeples;
use AGR\Core\Notifications;
use AGR\Core\Stats;

class D156_RetailDealer extends \AGR\Models\Occupation
{
  public function __construct($row)
  {
    parent::__construct($row);
    $this->id = 'D156_RetailDealer';
    $this->name = clienttranslate('Retail Dealer');
    $this->deck = 'D';
    $this->number = 156;
    $this->category = GOODS_PROVIDER;
    $this->desc = [
      clienttranslate(
        'Place 3 <GRAIN> and 3 <FOOD> on this card. Each time you use the __Resource Market__ action space, you also get 1 <GRAIN> and 1 <FOOD> from this card.'
      ),
    ];
    $this->players = '4+';
    $this->holder = true;
    $this->isCorbariusOrDulcinaria = true;
  }
  
  public function onBuy($player)
  {
    $types = [GRAIN, GRAIN, GRAIN, FOOD, FOOD, FOOD];

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
    return $this->isActionCardEvent($event, 'ResourceMarket') && 
      !Meeples::getResourcesOnCard($this->id, null, GRAIN)->empty();
  }
  
  public function onPlayerPlaceFarmer($player, $event)
  {
    $grain = Meeples::getResourcesOnCard($this->id, null, GRAIN)->first();
    $food = Meeples::getResourcesOnCard($this->id, null, FOOD)->first();
    
    return [
          'type' => NODE_SEQ, 
          'childs' => [
            $this->receiveNode($grain['id'],true),
            $this->receiveNode($food['id'],true),
          ]
        ];
  }
  
  public function getGainResourcesDescription()
  {
    return [
      'log' => clienttranslate('Gain ${resources_desc}'),
      'args' => [
        'resources_desc' => '1 <GRAIN>, 1 <FOOD>',
      ],
    ];
  }
  
  public function gainResources()
  {
    return;
  }
}
