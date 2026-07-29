<?php
namespace AGR\Cards\D;

use AGR\Helpers\CardRulings;

class D74_RoyalWood extends \AGR\Models\MinorImprovement
{
  public function __construct($row)
  {
    parent::__construct($row);
    $this->id = 'D74_RoyalWood';
    $this->name = clienttranslate('Royal Wood');
    $this->deck = 'D';
    $this->number = 74;
    $this->category = BUILDING_RESOURCE_PROVIDER;
    $this->desc = [
      clienttranslate(
        'At the end of each turn in which you use the __Farm Expansion__ action space or build an improvement, you get 1 <WOOD> back for every 2 <WOOD> paid during those actions (rounded down).'
      ),
    ];
    $this->cost = [
      FOOD => '1',
    ];
    $this->isCorbariusOrDulcinaria = true;
    $this->bannedStrong1or2p = true;
    $this->bannedStrong3or4p = true;

    $this->rulings = CardRulings::fromKeys([
      'TURN_MEANS_PERSON_ACTION',
    ]);
  }

  public function isListeningTo($event)
  {
    return $this->isQualifyingPay($event) ||
      $this->isActionEvent($event, 'PlaceFarmer') ||
      $this->isActionCardEvent($event, null);
  }

  // Only wood paid for building an improvement or on the Farm Expansion space counts
  protected function isQualifyingPay($event)
  {
    if (!$this->isActionEvent($event, 'Pay')) {
      return false;
    }
    $info = $event['sourceInfo'] ?? [];
    if (($info['sourceAction'] ?? null) == 'BuyCard') {
      return in_array($info['cardType'] ?? null, [MAJOR, MINOR]);
    }
    return in_array($info['sourceAction'] ?? null, ['Construct', 'Stables']) &&
      ($info['actionCardId'] ?? null) == 'ActionFarmExpansion';
  }

  public function onPlayerPlaceFarmer($player, $event)
  {
    $this->setExtraDatas('payWood', 0);
  }

  public function onPlayerAfterPay($player, $event)
  {
    $wood_number = 0;
    foreach ($event['paying'] as $id => $meeple) {
      if ($id === 'card') {
        continue;
      }
      if ($meeple['type'] == 'wood') {
        $wood_number = $wood_number + 1;
      }
    }
    if ($wood_number == 0) {
      return;
    }
    return [
      'action' => SPECIAL_EFFECT,
      'args' => [
        'cardId' => $this->id,
        'method' => 'setWood',
        'args' => [$wood_number],
      ],
    ];
  }

  public function onPlayerAfterPlaceFarmer($player, $args)
  {
    $gain = round(($this->getExtraDatas('payWood') - 1) / 2);
    if ($gain <= 0) {
      return null;
    }
    return [
      'type' => NODE_SEQ,
      'childs' => [
        [
          'action' => SPECIAL_EFFECT,
          'args' => [
            'cardId' => $this->id,
            'method' => 'setWoodZero',
            'args' => [],
          ],
        ],
        $this->gainNode([WOOD => $gain]),
      ],
    ];
  }

  public function setWoodZero()
  {
    $this->setExtraDatas('payWood', 0);
  }

  public function setWood($number)
  {
    $this->setExtraDatas('payWood', $this->getExtraDatas('payWood') + $number);
  }

  public function onBuy($player)
  {
    $this->setExtraDatas('payWood', 0);
  }
}
