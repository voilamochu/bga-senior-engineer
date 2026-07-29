<?php

namespace AGR\Cards\A;

use AGR\Models\MinorImprovement;

class A34_Loppers extends MinorImprovement
{
  public function __construct($row)
  {
    parent::__construct($row);
    $this->id = 'A34_Loppers';
    $this->name = clienttranslate('Loppers');
    $this->deck = 'A';
    $this->number = 34;
    $this->category = POINTS_PROVIDER;
    $this->desc = [
      clienttranslate(
        'Each time you build 1 or more fences, you can also use this card to exchange 1 <WOOD> and 1 <FENCE> in your supply for 2 <FOOD> and 1 bonus <SCORE>.'
      ),
    ];
    $this->cost = [
      WOOD => '1',
    ];
    $this->extraVp = true;
    $this->prerequisite = clienttranslate('2 Occupations');
    $this->occupationPrerequisites = ['min' => 2];
    $this->isArtifexOrBubulcus = true;
  }

  public function isListeningTo($event)
  {
    return $this->isActionEvent($event, 'Fencing') && count($event['fences']) >= 1;
  }

  public function onPlayerAfterFencing($player, $event)
  {
    return [
      'type' => NODE_SEQ,
      'optional' => true,
      'childs' => [
        $this->payGainNode([WOOD => 1, FENCE => 1], [FOOD => 2, SCORE => 1], 'Loppers', false)
      ],
    ];
  }
}
