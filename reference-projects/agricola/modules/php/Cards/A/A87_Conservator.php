<?php
namespace AGR\Cards\A;

class A87_Conservator extends \AGR\Models\Occupation
{
  public function __construct($row)
  {
    parent::__construct($row);
    $this->id = 'A87_Conservator';
    $this->name = clienttranslate('Conservator');
    $this->deck = 'A';
    $this->number = 87;
    $this->category = FARM_PLANNER;
    $this->desc = [
      clienttranslate('You can renovate your wooden house directly to stone without renovating it to clay first.'),
    ];
    $this->players = '1+';

    $this->rulings = [
      clienttranslate('You may decline this ability.'),
    ];
  }
}
