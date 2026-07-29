<?php
namespace AGR\Cards\C;

class C54_MarketStall extends \AGR\Models\MinorImprovement
{
  public function __construct($row)
  {
    parent::__construct($row);
    $this->id = 'C54_MarketStall';
    $this->name = clienttranslate('Market Stall');
    $this->deck = 'C';
    $this->number = 54;
    $this->category = FOOD_PROVIDER;
    $this->desc = [
      clienttranslate(
        'After the field phase of each harvest, you can exchange 1 <GRAIN> plus 1 <FENCE> (both from your supply) for 5 <FOOD>.'
      ),
    ];
    $this->cost = [ STABLE => '1',];
    $this->isCorbariusOrDulcinaria = true;

  }

  // LEGACY - REPRINTINGS OF THIS CARD RENAME IT MARKET BOOTH TO AVOID DUPE NAME WITH B8_MarketStall

  public function isListeningTo($event)
  {
    return $this->isPlayerEvent($event) && $event['type'] == 'EndHarvestFieldPhase';
  }

  public function onPlayerEndHarvestFieldPhase($player)
  {
    return $this->payGainNode([GRAIN => 1,FENCE => 1], [FOOD => 5], null, true);
  }
}
