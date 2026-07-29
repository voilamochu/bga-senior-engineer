<?php
namespace AGR\Cards\C;
use AGR\Helpers\Utils;
use AGR\Helpers\CardRulings;

class C105_BasketCarrier extends \AGR\Models\Occupation
{
  public function __construct($row)
  {
    parent::__construct($row);
    $this->id = 'C105_BasketCarrier';
    $this->name = clienttranslate('Basket Carrier');
    $this->deck = 'C';
    $this->number = 105;
    $this->category = GOODS_PROVIDER;
    $this->desc = [clienttranslate('Once each harvest, you can buy 1 <WOOD>, 1 <REED>, and 1 <GRAIN> for 2 <FOOD> total.')];
    $this->players = '1+';
    $this->isCorbariusOrDulcinaria = true;
    $this->exchanges = [Utils::formatExchange([FOOD => [WOOD => 1, REED => 1, GRAIN => 1], 'nb' => 2, 'max' => 1], $this->name, [HARVEST], $this->id)];

    $this->rulings = CardRulings::fromKeys([
      'MUST_USE_EXCHANGE_WINDOW',
    ]);
  }
}
