<?php
namespace AGR\Cards\A;

class A18_WheelPlow extends \AGR\Models\MinorImprovement
{
  public function __construct($row)
  {
    parent::__construct($row);
    $this->id = 'A18_WheelPlow';
    $this->name = clienttranslate('Wheel Plow');
    $this->deck = 'A';
    $this->number = 18;
    $this->category = FARM_PLANNER;
    $this->desc = [
      clienttranslate(
        'Once this game, when you use the __Farmland__ or __Cultivation__ action space with the first person you place in a round, you can plow 2 additional fields.'
      ),
    ];
    $this->cost = [
      WOOD => 2,
    ];
    $this->prerequisite = ('2 Occupations');
    $this->occupationPrerequisites = ['min' => 2];
    $this->isArtifexOrBubulcus = true;
  }

  public function isListeningTo($event)
  {
    return ($this->isActionCardEvent($event, 'Farmland') || 
      $this->isActionCardEvent($event, 'Cultivation')) && 
      !$this->isFlagged();
  }

  public function onPlayerPlaceFarmer($player, $event)
  {
    if ($player->countPlacedFarmers() == 1) {
      $childs = [
        'type' => NODE_SEQ,
        'optional' => true,
        'customDescription' => clienttranslate('Plow additional fields (Wheel Plow)'),
        'childs' => [
          [
            'action' => PLOW,
            'args' => ['source' => $this->name, 'cardId' => $this->id],
          ],
        ],
      ];

      $tmp = $childs;
      $tmp['customDescription'] = clienttranslate('Plow additional field (Wheel Plow)');
      $childs['childs'][] = $tmp;

      return [
        'type' => NODE_SEQ,
        'customDescription' => clienttranslate('Plow 2 additional fields'),
        'childs' => [
          $this->flagCardNode(),
          $childs,
        ]
      ];
    }
  }
}
