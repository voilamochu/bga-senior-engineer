<?php
namespace AGR\Cards\C;

use AGR\Helpers\Utils;
use AGR\Helpers\CardRulings;

class C109_SchnappsDistiller extends \AGR\Models\Occupation
{
  public function __construct($row)
  {
    parent::__construct($row);
    $this->id = 'C109_SchnappsDistiller';
    $this->name = clienttranslate('Schnapps Distiller');
    $this->deck = 'C';
    $this->number = 109;
    $this->category = FOOD_PROVIDER;
    $this->desc = [
      clienttranslate(
        'In the feeding phase of each harvest, you can use this card to turn exactly 1 <VEGETABLE> into 5 <FOOD>.'
      ),
    ];
    $this->players = '1+';
    $this->exchanges = [
      Utils::formatExchange([\VEGETABLE => [FOOD => 5], 'max' => 1], $this->name, [HARVEST], $this->id),
    ];

    $this->rulings = CardRulings::fromKeys([
      'MUST_USE_EXCHANGE_WINDOW',
    ]);
  }
}
