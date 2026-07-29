<?php
namespace AGR\Cards\C;

class C27_Blueprint extends \AGR\Models\MinorImprovement
{
  public function __construct($row)
  {
    parent::__construct($row);
    $this->id = 'C27_Blueprint';
    $this->name = clienttranslate('Blueprint');
    $this->deck = 'C';
    $this->number = 27;
    $this->category = ACTIONS_BOOSTER;
    $this->desc = [
      clienttranslate(
        'You can build the major improvements __Joinery__, __Pottery__, and __Basketmaker\'s Workshop__ even when taking a __Minor Improvement__ action. They each cost you 1 <STONE> less.'
      ),
    ];
    $this->cost = [
      FOOD => 1,
    ];

    $this->rulings = [
      clienttranslate('You must get a literal __Minor Improvement__ action, not just the ability to build a minor improvement such as from __Equipper__ (B131).'),
    ];
  }

  public function onPlayerComputeCardCosts($player, &$args)
  {
    if (!in_array($args['card']->getId(), ['Major_Joinery', 'Major_Pottery', 'Major_Basket'])) {
      return;
    }

    foreach ($args['costs']['trades'] as $trade) {
      if (isset($trade[STONE])) {
        $trade[STONE]--;
        if ($trade[STONE] <= 0) {
          unset($trade[STONE]);
        }
        $trade['sources'][] = $this->id;
        $args['costs']['trades'][] = $trade;
      }
    }
  }
}
