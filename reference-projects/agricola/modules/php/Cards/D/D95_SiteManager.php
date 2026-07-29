<?php
namespace AGR\Cards\D;
use AGR\Helpers\Utils;

class D95_SiteManager extends \AGR\Models\Occupation
{
  public function __construct($row)
  {
    parent::__construct($row);
    $this->id = 'D95_SiteManager';
    $this->name = clienttranslate('Site Manager');
    $this->deck = 'D';
    $this->number = 95;
    $this->category = ACTIONS_BOOSTER;
    $this->desc = [
      clienttranslate(
        'When you play this card, immediately build a major improvement. When paying its cost, you can replace up to 1 building resource of each type with 1 <FOOD> each.'
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
        Utils::wrapOptional([
          'action' => IMPROVEMENT,
          'args' => [
            'types' => [MAJOR],
          ]
        ]),
        $this->unflagCardNode(),
      ],
    ];
  }

  function calcTradeReplace($trade)
  {
    $replacement = [];
    $resList = [WOOD, CLAY, STONE, REED];
    for ($i = 1; $i < (1 << 4); $i++) {
      $res = [];
      for ($j = 0; $j < 4; $j++) {
        if ($i & (1 << $j)) {
          $res[] = $resList[$j];
        }
      }
      $copy = $trade;
      $flag = false;
      foreach ($res as $r) {
        if (isset($copy[$r])) {
          $flag = true;
          $copy[$r] -= 1;
          if ($copy[$r] <= 0) {
            unset($copy[$r]);
          }
          if (isset($copy[FOOD])) {
            $copy[FOOD] += 1;
          } else {
            $copy[FOOD] = 1;
          }
        }
      }
      if ($flag) {
        $copy['sources'][] = $this->id;
        $replacement[] = $copy;
      }
    }
    return $replacement;
  }

  public function onPlayerComputeCardCosts($player, &$args)
  {
    if (!$this->isFlagged()) {
      return;
    }
    $newTrades = [];
    foreach ($args['costs']['trades'] as $trade) {
      $newTrades = array_merge($newTrades, $this->calcTradeReplace($trade));
    }
    $args['costs']['trades'] = array_merge($args['costs']['trades'], $newTrades);
  }
}
