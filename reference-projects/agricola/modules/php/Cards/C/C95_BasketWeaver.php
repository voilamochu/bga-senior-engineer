<?php
namespace AGR\Cards\C;

class C95_BasketWeaver extends \AGR\Models\Occupation
{
  public function __construct($row)
  {
    parent::__construct($row);
    $this->id = 'C95_BasketWeaver';
    $this->name = clienttranslate('Basket Weaver');
    $this->deck = 'C';
    $this->number = 95;
    $this->category = ACTIONS_BOOSTER;
    $this->desc = [
      clienttranslate(
        'When you play this card, immediately build the __Basketmaker\'s Workshop__ major improvement for 1 <STONE> and 1 <REED>.'
      ),
    ];
    $this->players = '1+';
    $this->isCorbariusOrDulcinaria = true;
    $this->replacesCostFor = ['ComputeCardCosts'];
  }

  public function onBuy($player)
  {
    return [
      'type' => NODE_SEQ,
      'childs' => [
        $this->flagCardNode(),
        [
          'action' => IMPROVEMENT,
          'args' => [
            'types' => [MAJOR],
            'allowedPurchases' => ['Major_Basket'],
            'trueAction' => false,
          ]
        ],
        $this->unflagCardNode(),
      ],
    ];
  }

  public function onPlayerComputeCardCosts($player, &$args)
  {
    if (!$this->isFlagged()) {
      return;
    }

    if (!in_array($args['card']->getId(), ['Major_Basket'])) {
      return;
    }

    foreach ($args['costs']['trades'] as $trade) {
      // Strip any previous resource costs from the reference trade, then
      // apply BasketWeaver's fixed price (1 STONE + 1 REED) and append.
      foreach ($trade as $key => $value) {
        if (!in_array($key, ['sources', 'nb', 'max', 'bonusChoiceIndex', 'card'])) {
          unset($trade[$key]);
        }
      }
      $trade[STONE] = 1;
      $trade[REED] = 1;
      $trade['sources'][] = $this->id;
      $args['costs']['trades'][] = $trade;
    }
  }
}
