<?php
namespace AGR\Cards\C;

use AGR\Managers\Farmers;

class C131_PrivateTeacher extends \AGR\Models\Occupation
{
  public function __construct($row)
  {
    parent::__construct($row);
    $this->id = 'C131_PrivateTeacher';
    $this->name = clienttranslate('Private Teacher');
    $this->deck = 'C';
    $this->number = 131;
    $this->category = ACTIONS_BOOSTER;
    $this->desc = [
      clienttranslate(
        'Each time you use the __Grain Seeds__ action space when any __Lessons__ action space is occupied, you can also play an occupation for an occupation cost of 1 <FOOD>.'
      ),
    ];
    $this->players = '3+';
    $this->isCorbariusOrDulcinaria = true;

    $this->rulings = [
      clienttranslate('If the played occupation has an effect each time you use __Grain Seeds__, it also triggers immediately.'),
    ];
  }
  
  public function isListeningTo($event)
  {
    return $this->isActionCardEvent($event, 'GrainSeeds');
  }

  public function onPlayerPlaceFarmer($player, $event)
  {
    if ((!Farmers::getOnCard('ActionLessons')->empty()) ||
        (!Farmers::getOnCard('ActionLessons3')->empty()) ||
        (!Farmers::getOnCard('ActionLessons4')->empty()))
    {
      return [
        'action' => OCCUPATION,
        'cardId' => $this->id,
        'optional' => true,
        'args' => ['max' => 1, 'cost' => [FOOD => 1]],
      ];
    }
  }
}
