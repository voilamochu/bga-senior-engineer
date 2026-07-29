<?php
namespace AGR\Cards\D;
use AGR\Helpers\Utils;

class D91_Plowman extends \AGR\Models\Occupation
{
  public function __construct($row)
  {
    parent::__construct($row);
    $this->id = 'D91_Plowman';
    $this->name = clienttranslate('Plowman');
    $this->deck = 'D';
    $this->number = 91;
    $this->category = FARM_PLANNER;
    $this->desc = [
      clienttranslate(
        'Add 4, 7, and 10 to the current round and place a field tile on each corresponding round space. At the start of these rounds, you can plow the field for 1 <FOOD>.'
      ),
    ];
    $this->players = '1+';
  }

  public function onBuy($player)
  {
    return $this->futureMeeplesNode([FIELDPLUS => 1], ['+4', '+7', '+10'], true, $this->id);
  }

  public function getReceiveFlow($meeple)
  {
    return $this->getReceiveFlowWithCost($meeple, Utils::formatCost([FOOD => 1]), clienttranslate('Plowman effect'));
  }
}
