<?php
namespace AGR\Cards\D;

use AGR\Helpers\Utils;

class D13_Trowel extends \AGR\Models\MinorImprovement
{
  public function __construct($row)
  {
    parent::__construct($row);
    $this->id = 'D13_Trowel';
    $this->name = clienttranslate('Trowel');
    $this->deck = 'D';
    $this->number = 13;
    $this->category = FARM_PLANNER;
    $this->desc = [
      clienttranslate(
        'At any time, you can renovate your house to stone. From a wooden house, this costs 1 <STONE>, 1 <REED>, and 1 <FOOD> per room. From a clay house, this costs 1 <STONE> per room.'
      ),
    ];
    $this->cost = [
      WOOD => 1,
    ];
    $this->isCorbariusOrDulcinaria = true;
    $this->replacesCostFor = ['ComputeCostsRenovation'];
    $this->rulings = [
      clienttranslate(
        'This is a standalone action you can take at any time; it does not modify __Renovation__ actions given by action spaces or cards such as __Prophet__ (E094).'
      ),
    ];
  }

  public function isListeningTo($event)
  {
    return $this->isAnytime($event);
  }

  public function onPlayerAtAnytime($player, $event)
  {
    return [
      'action' => RENOVATION,
      'pId' => $this->pId,
      'args' => ['toStone' => true, 'actionCardId' => 'D13_Trowel'],
    ];
  }

  public function onPlayerComputeCostsRenovation($player, &$args)
  {
    if ($args['actionCardId'] != 'D13_Trowel') {
      return;
    }
    if ($player->getRoomType() == 'roomWood') {
      foreach ($args['costs']['trades'] as &$trade) {
        $trade[STONE] = 1;
        $trade[FOOD] = 1;
        $trade['sources'][] = $this->id;
      }
      foreach ($args['costs']['fees'] as &$fee) {
        $fee[REED] = $player->countRooms();
        $fee['sources'][] = $this->id;
      }
    } else {
      foreach ($args['costs']['trades'] as &$trade) {
        $trade[STONE] = 1;
        $trade['sources'][] = $this->id;
      }
      foreach ($args['costs']['fees'] as &$fee) {
        unset($fee[REED]);
        $fee['sources'][] = $this->id;
      }
    }
  }
}
