<?php
namespace AGR\Cards\D;
use AGR\Managers\Meeples;
use AGR\Core\Notifications;
use AGR\Helpers\CardRulings;

// TODO: change css so 7th+ meeple don't fall off the card

class D124_Emissary extends \AGR\Models\Occupation
{
  public function __construct($row)
  {
    parent::__construct($row);
    $this->id = 'D124_Emissary';
    $this->name = clienttranslate('Emissary');
    $this->deck = 'D';
    $this->number = 124;
    $this->category = BUILDING_RESOURCE_PROVIDER;
    $this->desc = [
      clienttranslate(
        'At any time, you can place a good from your supply on this card to get 1 <STONE>. You must place different goods on this card. (<FOOD> is also considered a good.)'
      ),
    ];
    $this->players = '1+';
    $this->holder = true;
    $this->isCorbariusOrDulcinaria = true;

    $this->rulings = CardRulings::fromKeys([
      'ANIMALS_MUST_BE_ACCOMMODATED',
    ]);
  }

  public function isListeningTo($event)
  {
    return $this->isAnytime($event) &&
      $this->getUsableGoods($this->pId) != [];
  }

  public function onPlayerAtAnytime($player, $event)
  {
    $goods = $this->getUsableGoods($player->getId());
    $childs = [];
    
    foreach ($goods as $good) {
      $childs[] = [
        'type' => NODE_SEQ,
        'childs' => [
          [
            'action' => SPECIAL_EFFECT,
            'args' => [
              'cardId' => $this->id,
              'method' => 'reserveGood',
              'args' => [$good],
            ],
          ],
          $this->payGainNode([$good => 1], [STONE => 1], null, false),
          [
            'action' => SPECIAL_EFFECT,
            'args' => [
              'cardId' => $this->id,
              'method' => 'notifyGood',
              'args' => [$good],
            ],
          ],
        ],
      ];
    }

    return [
      'type' => NODE_XOR,
      'customDescription' => 'Pay a good to gain 1 <STONE>',
      'childs' => $childs,
    ];
  }

  public function getGoods()
  {
    $goods = [WOOD, CLAY, STONE, REED, GRAIN, VEGETABLE, FOOD, SHEEP, PIG, CATTLE];
    $available = [];

    foreach ($goods as $good) {
      if (Meeples::getResourcesOnCard($this->id, null, $good)->empty()) {
        $available[] = $good;
      }
    }
    return $available;
  }

  public function getUsableGoods($pId)
  {
    $reserve = $this->getPlayer()->countAnimalsInReserve();
    return array_values(array_filter(
      $this->getGoods(),
      fn($good) => Meeples::countAllResource($pId, $good) > 0 && ($reserve[$good] ?? 0) == 0
    ));
  }

  public function isIndependentReserveGood() {
    return true;
  }

  public function reserveGood($type) {
    if (!Meeples::getResourcesOnCard($this->id, null, $type)->empty()) {
      return;
    }
    Meeples::createResourceOnCard($type, $this->id);
  }

  public function isIndependentNotifyGood() {
    return true;
  }

  public function notifyGood($type) {
    $meeples = Meeples::getResourcesOnCard($this->id, null, $type);
    Notifications::accumulate($meeples, true);
  }

  // These two functions for legacy games in progress
  public function isIndependentAddGood() {
    return true;
  }

  public function addGood($type) {
    if (!Meeples::getResourcesOnCard($this->id, null, $type)->empty()) {
      return;
    }
    $created = Meeples::createResourceOnCard($type, $this->id);
    Notifications::accumulate(Meeples::getMany($created), true);
  }
}
