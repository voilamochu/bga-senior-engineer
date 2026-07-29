<?php
namespace AGR\Cards\B;

class B1_UpscaleLifestyle extends \AGR\Models\MinorImprovement
{
  public function __construct($row)
  {
    parent::__construct($row);
    $this->id = 'B1_UpscaleLifestyle';
    $this->name = clienttranslate('Upscale Lifestyle');
    $this->deck = 'B';
    $this->number = 1;
    $this->category = FARM_PLANNER;
    $this->desc = [
      clienttranslate(
        'You immediately get 5 <CLAY> and a __Renovation__ action. If you take the action, you must pay the renovation cost.'
      ),
    ];
    $this->passing = true;
    $this->cost = [
      WOOD => 3,
    ];
    $this->isArtifexOrBubulcus = true;
  }
  
  public function onBuy($player)
  {
    return [
      'type' => NODE_SEQ,
      'childs' => [
        $this->gainNode([CLAY => 5]),
        [
          'type' => NODE_SEQ,
          'optional' => true,
          'childs' => [
            [
              'action' => RENOVATION,
              'pId' => $this->pId,
            ]
          ]
        ]
      ]
    ];
  }
}
