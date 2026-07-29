<?php
namespace AGR\Cards\A;

use AGR\Core\Engine;
use AGR\Core\Globals;

class A114_SeasonalWorker extends \AGR\Models\Occupation
{
  public function __construct($row)
  {
    parent::__construct($row);
    $this->id = 'A114_SeasonalWorker';
    $this->name = clienttranslate('Seasonal Worker');
    $this->deck = 'A';
    $this->number = 114;
    $this->category = CROP_PROVIDER;
    $this->desc = [
      clienttranslate(
        'Each time you use the __Day Laborer__ action space, you get 1 additional <GRAIN>. From round 6 on, you can choose to get 1 <VEGETABLE> instead.'
      ),
    ];
    $this->players = '1+';
  }

  public function isListeningTo($event)
  {
    return $this->isActionCardEvent($event, 'DayLaborer');
  }

  public function onPlayerPlaceFarmer($player, $args)
  {
    if (Globals::getTurn() < 6) {
      return $this->gainNode([GRAIN => 1]);
    } else {
      return [
        'type' => NODE_XOR,
        'childs' => [$this->gainNode([GRAIN => 1]), $this->gainNode([\VEGETABLE => 1])],
      ];
    }
  }
}
