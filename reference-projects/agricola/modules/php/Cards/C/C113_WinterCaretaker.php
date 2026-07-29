<?php
namespace AGR\Cards\C;
use AGR\Helpers\Utils;

class C113_WinterCaretaker extends \AGR\Models\Occupation
{
  public function __construct($row)
  {
    parent::__construct($row);
    $this->id = 'C113_WinterCaretaker';
    $this->name = clienttranslate('Winter Caretaker');
    $this->deck = 'C';
    $this->number = 113;
    $this->category = CROP_PROVIDER;
    $this->desc = [
      clienttranslate(
        'When you play this card, you immediately get 1 <GRAIN>. At the end of each harvest, you can buy exactly 1 <VEGETABLE> for 2 <FOOD>.'
      ),
    ];
    $this->players = '1+';
  }

  public function onBuy($player)
  {
    return $this->gainNode([GRAIN => 1]);
  }

  public function isListeningTo($event)
  {
    return $this->isPlayerEvent($event) && $event['type'] == 'EndHarvest';
  }

  public function onPlayerEndHarvest($player)
  {
    return [
      'type' => NODE_SEQ,
      'optional' => true,
      'childs' => [
        [
          'action' => PAY,
          'args' => [
            'nb' => 1,
            'costs' => Utils::formatCost([FOOD => 2]),
            'source' => $this->name,
          ],
        ],
        $this->gainNode([VEGETABLE => 1])
      ]
    ];
  }
}
