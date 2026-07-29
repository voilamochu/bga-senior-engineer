<?php
namespace AGR\Cards\E;
use AGR\Helpers\Utils;
use AGR\Helpers\CardRulings;

class E153_StoneSculptor extends \AGR\Models\Occupation
{
  public function __construct($row)
  {
    parent::__construct($row);
    $this->id = 'E153_StoneSculptor';
    $this->name = clienttranslate('Stone Sculptor');
    $this->deck = 'E';
    $this->author = 'keith';
    $this->number = 153;
    $this->category = 'BONUS_POINTS';
    $this->desc = [
      clienttranslate('Each harvest, you can use this card to exchange exactly 1 <STONE> for 1 bonus <SCORE> and 1 <FOOD>.'),
    ];
    $this->players = '4+';
    $this->extraVp = true;
    $this->implemented = true;
    $this->exchanges = [Utils::formatExchange([STONE => [SCORE => 1, FOOD => 1], 'max' => 1], $this->name, [HARVEST], $this->id) + ['scoreCardId' => $this->id]];

    $this->rulings = CardRulings::fromKeys([
      'MUST_USE_EXCHANGE_WINDOW',
    ]);
  }
}
