<?php
namespace AGR\Cards\D;
use AGR\Helpers\Utils;
use AGR\Helpers\CardRulings;

class D155_Ebonist extends \AGR\Models\Occupation
{
  public function __construct($row)
  {
    parent::__construct($row);
    $this->id = 'D155_Ebonist';
    $this->name = clienttranslate('Ebonist');
    $this->deck = 'D';
    $this->number = 155;
    $this->category = GOODS_PROVIDER;
    $this->desc = [
      clienttranslate('Each harvest, you can use this card to turn exactly 1 <WOOD> into 1 <FOOD> and 1 <GRAIN>.'),
    ];
    $this->players = '4+';
    $this->exchanges = [Utils::formatExchange([WOOD => [FOOD => 1, GRAIN => 1], 'max' => 1], $this->name, [HARVEST], $this->id)];
    $this->isCorbariusOrDulcinaria = true;

    $this->rulings = CardRulings::fromKeys([
      'MUST_USE_EXCHANGE_WINDOW',
    ]);
  }
}
