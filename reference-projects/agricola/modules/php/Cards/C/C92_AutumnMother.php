<?php
namespace AGR\Cards\C;
use AGR\Helpers\Utils;

class C92_AutumnMother extends \AGR\Models\Occupation
{
  public function __construct($row)
  {
    parent::__construct($row);
    $this->id = 'C92_AutumnMother';
    $this->name = clienttranslate('Autumn Mother');
    $this->deck = 'C';
    $this->number = 92;
    $this->category = ACTIONS_BOOSTER;
    $this->desc = [
      clienttranslate(
        'Immediately before each harvest, if you have room in your house, you can take a __Family Growth__ action for 3 <FOOD>.'
      ),
    ];
    $this->players = '1+';
    $this->holder = true;
    $this->isCorbariusOrDulcinaria = true;
    $this->bannedWeak1or2p = true;
  }

  public function isListeningTo($event)
  {
    return $this->isPlayerEvent($event) && $event['type'] == 'BeforeHarvest';
  }

  public function onPlayerBeforeHarvest($player, $event)
  {
    return [
      'type' => NODE_SEQ,
      'optional' => true,
      'childs' => [
        [
          'action' => PAY,
          'optional' => false,
          'args' => [
                'nb' => 1,
                'costs' => Utils::formatCost([FOOD => 3]),
                'source' => $this->name,
              ],
          'source' => $this->name,
        ],[
        'action' => WISHCHILDREN,
        'cardId' => $this->id,
        'optional' => false,
        'args' => ['constraints' => ['freeRoom'], 'cardLocation' => $this->id],
        'source' => $this->name,],],            
      ];
  }
}