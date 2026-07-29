<?php
namespace AGR\Cards\E;


class E17_SkimmerPlow extends \AGR\Models\MinorImprovement
{
  public function __construct($row)
  {
    parent::__construct($row);
    $this->id = 'E17_SkimmerPlow';
    $this->name = clienttranslate('Skimmer Plow');
    $this->deck = 'E';
    $this->author = 'beso';
    $this->number = 17;
    $this->category = 'FARMYARD_-_PLOWING';
    $this->desc = [
      clienttranslate(
        'Each time you use the __Farmland__ or __Cultivation__  action space, you can plow 2 fields instead of 1. Each time you sow, you must place 1 fewer good on each field you sow.'
      ),
    ];
    $this->vp = 0;
    $this->cost = [
      WOOD => 1,
    ];
    $this->prerequisite = clienttranslate('2 Occupations');
    $this->occupationPrerequisites = ['min' => 2];
  }

  public function isListeningTo($event)
  {
    return $this->isActionCardEvent($event, 'Farmland') ||
      $this->isActionCardEvent($event, 'Cultivation');
  }

  public function onPlayerPlaceFarmer($player, $event)
  {
    return [
      'type' => NODE_SEQ,
      'optional' => true,
      'childs' => [
        [
          'action' => PLOW,
          'args' => ['source' => $this->name, 'cardId' => $this->id],
        ],
      ],
    ];
  }
}
