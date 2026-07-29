<?php
namespace AGR\Cards\C;


class C91_PlowHero extends \AGR\Models\Occupation
{
  public function __construct($row)
  {
    parent::__construct($row);
    $this->id = 'C91_PlowHero';
    $this->name = clienttranslate('Plow Hero');
    $this->deck = 'C';
    $this->number = 91;
    $this->category = FARM_PLANNER;
    $this->desc = [
      clienttranslate(
        'Each time you use the __Farmland__ or __Cultivation__ action space with the first person you place in a round, you can plow 1 additional field for 1 <FOOD>.'
      ),
    ];
    $this->players = '1+';
    $this->isCorbariusOrDulcinaria = true;
  }

  public function isListeningTo($event)
  {
    return $this->isActionCardEvent($event, 'Farmland') || 
      $this->isActionCardEvent($event, 'Cultivation');
  }

  public function onPlayerPlaceFarmer($player, $event)
  {
    if ($player->countPlacedFarmers() != 1) {
      return;
    }

    return [
      'type' => NODE_SEQ,
      'optional' => true,
      'childs' => [
        $this->payNode([FOOD => 1]),
        [
          'action' => PLOW,
          'args' => ['source' => $this->name, 'cardId' => $this->id],
        ],
      ],
    ];
  }  
}
