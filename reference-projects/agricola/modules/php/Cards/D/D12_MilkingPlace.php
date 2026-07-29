<?php
namespace AGR\Cards\D;
use AGR\Core\Notifications;
use AGR\Helpers\Utils;

class D12_MilkingPlace extends \AGR\Models\MinorImprovement
{
  public function __construct($row)
  {
    parent::__construct($row);
    $this->id = 'D12_MilkingPlace';
    $this->name = clienttranslate('Milking Place');
    $this->deck = 'D';
    $this->number = 12;
    $this->category = FARM_PLANNER;
    $this->desc = [
      clienttranslate(
        'In the feeding phase of each harvest, you get 1 <FOOD>. You can no longer hold animals in your house (not even via another card).'
      ),
    ];
    $this->vp = 1;
    $this->cost = [
      GRAIN => 1,
    ];
  }

  public function onBuy($player)
  {
    Notifications::updateDropZones($player);
  }

  public function isListeningTo($event)
  {
    return $this->isPlayerEvent($event) && $event['type'] == 'HarvestFeedingPhase';
  }

  public function onPlayerHarvestFeedingPhase($player)
  {
    return $this->gainNode([FOOD => 1]);
  }

  public function onPlayerComputeDropZones($player, &$args)
  {
    Utils::filter($args['zones'], function ($zone) {
      return $zone['type'] != 'room' && $zone['type'] != 'D148_special';
    });
    // TODO : remove animal if any in the house ??
  }
}
