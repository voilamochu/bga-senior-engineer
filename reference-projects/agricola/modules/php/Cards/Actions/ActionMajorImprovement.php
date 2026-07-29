<?php
namespace AGR\Cards\Actions;

class ActionMajorImprovement extends \AGR\Models\ActionCard
{
  public function __construct($row)
  {
    parent::__construct($row);
    $this->id = 'ActionMajorImprovement';
    $this->name = clienttranslate('Improvements');
    $this->desc = ['1<MAJOR>/<MINOR>'];
    $this->tooltipDesc = [
      clienttranslate('[Build 1 major improvement or play 1 minor improvement]'),
      '1<MAJOR>/<MINOR>',
    ];
    $this->tooltip = [clienttranslate('You can either build 1 major improvement or play 1 minor improvement')];

    $this->isNotBeginner = true;
    $this->stage = 1;
    $this->flow = [
      'action' => IMPROVEMENT,
      'args' => [
        'types' => [MINOR, MAJOR],
        'actionType' => 'MajorOrMinor',
      ],
    ];
  }
}
