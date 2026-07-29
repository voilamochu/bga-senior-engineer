<?php
namespace AGR\Cards\B;


class B91_AssistantTiller extends \AGR\Models\Occupation
{
  public function __construct($row)
  {
    parent::__construct($row);
    $this->id = 'B91_AssistantTiller';
    $this->name = clienttranslate('Assistant Tiller');
    $this->deck = 'B';
    $this->number = 91;
    $this->category = FARM_PLANNER;
    $this->desc = [clienttranslate('Each time you use the __Day Laborer__ action space, you can also plow 1 field.')];
    $this->players = '1+';
  }

  public function isListeningTo($event)
  {
    return $this->isActionCardEvent($event, 'DayLaborer');
  }

  public function onPlayerPlaceFarmer($player, $event)
  {
    return [
      'action' => PLOW,
      'cardId' => $this->id,
      'optional' => true,
      'source' => $this->name,
    ];
  }
}
