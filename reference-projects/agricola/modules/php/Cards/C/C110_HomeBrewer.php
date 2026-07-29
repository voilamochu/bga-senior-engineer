<?php
namespace AGR\Cards\C;

class C110_HomeBrewer extends \AGR\Models\Occupation
{
  public function __construct($row)
  {
    parent::__construct($row);
    $this->id = 'C110_HomeBrewer';
    $this->name = clienttranslate('Home Brewer');
    $this->deck = 'C';
    $this->number = 110;
    $this->category = FOOD_PROVIDER;
    $this->desc = [
      clienttranslate(
        'After the field phase of each harvest, you can use this card to turn exactly 1 <GRAIN> into your choice of 3 <FOOD> or 1 bonus <SCORE>.'
      ),
    ];
    $this->players = '1+';
    $this->extraVp = true;
  }

  public function preFeedingGoodsWanted()
  {
    return [GRAIN];
  }

  public function isListeningTo($event)
  {
    return $this->isPlayerEvent($event) && $event['type'] == 'EndHarvestFieldPhase';
  }

  public function onPlayerEndHarvestFieldPhase($player)
  {
    return [
      'type' => NODE_XOR,
      'optional' => true,
      'countAsUse' => true,
      'doableRequiresChild' => true,
      'childs' => [
        $this->payGainNode([GRAIN => 1], [FOOD => 3], null, false),
        $this->payGainNode([GRAIN => 1], [SCORE => 1], null, false),
      ],
    ];
  }
}
