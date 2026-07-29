<?php
namespace AGR\Cards\E;
use AGR\Managers\Farmers;
use AGR\Managers\Meeples;
use AGR\Core\Notifications;

class E3_TeaTime extends \AGR\Models\MinorImprovement
{
  public function __construct($row)
  {
    parent::__construct($row);
    $this->id = 'E3_TeaTime';
    $this->name = clienttranslate('Tea Time');
    $this->deck = 'E';
    $this->author = 'beso';
    $this->number = 3;
    $this->category = 'PASSING_-_ACTION_-_FARMYARD';
    $this->desc = [
      clienttranslate(
        'Immediately return your person on the __Grain Utilization__ action space home; you can place it again later this round.'
      ),
    ];
    $this->passing = true;
    $this->cost = [
      FOOD => 1,
    ];
    $this->prerequisite = clienttranslate('Own Person on Grain Utilization');
  }

  public function isBuyable($player, $ignoreResources = false, $args = [])
  {
    if (Farmers::getOnCard('ActionGrainUtilization', $this->getPlayer()->getId())->empty()) {
      return false;
    }

    return parent::isBuyable($player, $ignoreResources, $args);
  }

  public function onBuy($player)
  {
    $farmer = Farmers::getOnCard('ActionGrainUtilization', $this->getPlayer()->getId())->first();
    $player->returnHomeOne($farmer['id']);
    Notifications::returnHomeOne($player, Farmers::getMany($farmer['id']));
  }
}
