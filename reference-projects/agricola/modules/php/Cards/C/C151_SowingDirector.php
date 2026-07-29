<?php
namespace AGR\Cards\C;

use AGR\Helpers\UsedText;

class C151_SowingDirector extends \AGR\Models\Occupation
{
  public function __construct($row)
  {
    parent::__construct($row);
    $this->id = 'C151_SowingDirector';
    $this->name = clienttranslate('Sowing Director');
    $this->deck = 'C';
    $this->number = 151;
    $this->category = ACTIONS_BOOSTER;
    $this->desc = [
      clienttranslate(
        'Each time after another player uses the __Grain Utilization__ action space, you get a __Sow__ action.'
      ),
    ];
    $this->players = '4+';
    $this->isCorbariusOrDulcinaria = true;
    $this->usedText = UsedText::get('SOWS');
  }
  
  public function isListeningTo($event)
  {
    return $this->isActionEvent($event, 'PlaceFarmer', 'opponent') && 
      $event['actionCardType'] == 'GrainUtilization';
  }

  public function onOpponentAfterPlaceFarmer($player, $args)
  {
    return [
      'type' => NODE_SEQ,
      'pId' => $this->pId,
      'childs' => [
        [
          'type' => NODE_SEQ,
          'optional' => true,
          'forceConfirmation' => true,
          'pId' => $this->pId,
          'childs' => [
            [
              'action' => SOW,
              'pId' => $this->pId,
              'optional' => true,
            ],
            [
              'action' => SPECIAL_EFFECT,
              'args' => [
                'cardId' => $this->id,
                'method' => 'bumpUsed',
              ],
            ],
          ],
        ],
      ],
    ];
  }

  public function bumpUsed()
  {
    $this->incStats('used');
  }

  public function isIndependentBumpUsed()
  {
    return true;
  }
}
