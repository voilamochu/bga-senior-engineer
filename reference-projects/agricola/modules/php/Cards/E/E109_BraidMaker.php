<?php
namespace AGR\Cards\E;
use AGR\Helpers\Utils;
use AGR\Helpers\CardRulings;

class E109_BraidMaker extends \AGR\Models\Occupation
{
  public function __construct($row)
  {
    parent::__construct($row);
    $this->id = 'E109_BraidMaker';
    $this->name = clienttranslate('Braid Maker');
    $this->deck = 'E';
    $this->author = 'agentricola';
    $this->number = 109;
    $this->category = FOOD;
    $this->desc = [
      clienttranslate(
        'Each harvest, you can use this card to exchange 1 <REED> for 2 <FOOD>. You can build the  __Basketmaker\'s Workshop__ for 1 <REED> and 1 <STONE> even when taking a __Minor Improvement__ action. '
      ),
    ];
    $this->players = '1+';
    $this->exchanges = [Utils::formatExchange([\REED => [FOOD => 2], 'max' => 1], $this->name, [HARVEST], $this->id)];
    $this->replacesCostFor = ['ComputeCardCosts'];

    $this->rulings = CardRulings::fromKeys([
      'MUST_USE_EXCHANGE_WINDOW',
    ]);
  }

  public function onPlayerComputeCardCosts($player, &$args)
  {
    if (!in_array($args['card']->getId(), ['Major_Basket'])) {
      return;
    }

    foreach ($args['costs']['trades'] as $trade) {
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
