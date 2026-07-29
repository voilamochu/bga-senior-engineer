<?php
namespace AGR\Cards\D;
use AGR\Helpers\Utils;
use AGR\Helpers\CardRulings;

class D108_StoneCarver extends \AGR\Models\Occupation
{
  public function __construct($row)
  {
    parent::__construct($row);
    $this->id = 'D108_StoneCarver';
    $this->name = clienttranslate('Stone Carver');
    $this->deck = 'D';
    $this->number = 108;
    $this->category = FOOD_PROVIDER;
    $this->desc = [clienttranslate('Each harvest, you can use this card to turn exactly 1 <STONE> into 3 <FOOD>.')];
    $this->players = '1+';
    $this->exchanges = [Utils::formatExchange([\STONE => [FOOD => 3], 'max' => 1], $this->name, [HARVEST], $this->id)];

    $this->rulings = CardRulings::fromKeys([
      'MUST_USE_EXCHANGE_WINDOW',
    ]);
  }
}
