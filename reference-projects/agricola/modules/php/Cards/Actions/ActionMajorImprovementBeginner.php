<?php
namespace AGR\Cards\Actions;

class ActionMajorImprovementBeginner extends \AGR\Models\ActionCard
{
  public function __construct($row)
  {
    parent::__construct($row);
    $this->id = 'ActionMajorImprovementBeginner';
    $this->name = clienttranslate('Improvements');
    $this->actionCardType = 'MajorImprovement';
    $this->desc = ['1 <MAJOR>'];
    $this->tooltipDesc = [clienttranslate('[Build 1 major improvement]'), '1 <MAJOR>'];
    $this->tooltip = [clienttranslate('You can build 1 major improvement')];

    $this->isBeginner = true;
    $this->stage = 1;
    $this->flow = [
      'action' => IMPROVEMENT,
      'args' => [
        'types' => [MAJOR],
      ],
    ];
  }
}
