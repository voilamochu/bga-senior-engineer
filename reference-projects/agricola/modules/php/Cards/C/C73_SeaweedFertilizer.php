<?php
namespace AGR\Cards\C;
use AGR\Core\Globals;

class C73_SeaweedFertilizer extends \AGR\Models\MinorImprovement
{
  public function __construct($row)
  {
    parent::__construct($row);
    $this->id = 'C73_SeaweedFertilizer';
    $this->name = clienttranslate('Seaweed Fertilizer');
    $this->deck = 'C';
    $this->number = 73;
    $this->category = CROP_PROVIDER;
    $this->desc = [
      clienttranslate(
        'Each time after you take an unconditional __Sow__ action, you get 1 <GRAIN> from the general supply. From round 11 on, you can get 1 <VEGETABLE> instead.'
      ),
    ];
    $this->cost = [
      FOOD => 2,
    ];
    $this->isCorbariusOrDulcinaria = true;
  }

  public function isListeningTo($event)
  {
    return $this->isActionEvent($event, 'Sow') &&
      $event['unconditional'];
  }

  public function onPlayerAfterSow($player, $event)
  {
    if (Globals::getTurn() < 11) {
      return $this->gainNode([GRAIN => 1]);
    } else {
      return [
        'type' => NODE_XOR,
        'childs' => [$this->gainNode([GRAIN => 1]), $this->gainNode([VEGETABLE => 1])],
      ];
    }
  }
}
