<?php
namespace AGR\Cards\A;
use AGR\Helpers\Utils;
use AGR\Helpers\CardRulings;

class A93_BedMaker extends \AGR\Models\Occupation
{
  public function __construct($row)
  {
    parent::__construct($row);
    $this->id = 'A93_BedMaker';
    $this->name = clienttranslate('Bed Maker');
    $this->deck = 'A';
    $this->number = 93;
    $this->category = ACTIONS_BOOSTER;
    $this->desc = [
      clienttranslate(
        'Each time you add rooms to your house, you can also pay 1 <WOOD> and 1 <GRAIN> to immediately get a __Family Growth with Room Only__ action.'
      ),
    ];
    $this->players = '1+';
    $this->holder = true;
    $this->isArtifexOrBubulcus = true;

    $this->rulings = CardRulings::fromKeys([
      'EXACTLY_ONE_GROWTH_ACTION',
    ]);
  }

  public function isListeningTo($event)
  {
    return $this->isActionEvent($event, 'Construct');
  }

  public function onPlayerAfterConstruct($player, $event)
  {
    $flow = [
      'type' => NODE_SEQ,
      'optional' => true,
      'childs' => [],
    ];

    $flow['childs'][] = [
      'type' => NODE_SEQ,
      'optional' => true,
      'childs' => [
        [
          'action' => PAY,
          'optional' => false,
          'args' => [
                'nb' => 1,
                'costs' => Utils::formatCost([WOOD => 1,GRAIN =>1]),
                'source' => $this->name,
              ],
          'source' => $this->name,
        ],[
        'action' => WISHCHILDREN,
        'cardId' => $this->id,
        'optional' => false,
        'args' => ['constraints' => ['freeRoom'], 'cardLocation' => $this->id],
        'source' => $this->name,],],            
      ];

    return $flow;
  }
}