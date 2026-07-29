<?php
namespace AGR\Cards\C;

use AGR\Core\Globals;
use AGR\Core\Engine;
use AGR\Helpers\CardRulings;

class C48_Farmstead extends \AGR\Models\MinorImprovement
{
  public function __construct($row)
  {
    parent::__construct($row);
    $this->id = 'C48_Farmstead';
    $this->name = clienttranslate('Farmstead');
    $this->deck = 'C';
    $this->number = 48;
    $this->category = FOOD_PROVIDER;
    $this->desc = [
      clienttranslate('After each turn in which you make at least one unused farmyard space used, you get 1 <FOOD>.'),
    ];
    $this->prerequisite = clienttranslate('1 Occupation');
    $this->occupationPrerequisites = ['min' => 1];
    $this->isCorbariusOrDulcinaria = true;

    $this->rulings = CardRulings::fromKeys([
      'TURN_MEANS_PERSON_ACTION',
    ]);
  }

  public function isListeningTo($event)
  {
    return $this->isActionEvent($event, 'Plow')
      || $this->isActionEvent($event, 'Construct')
      || $this->isActionEvent($event, 'Fencing')
      || $this->isActionEvent($event, 'Stables');
  }

  public function onPlayerAfterPlow($player, $event)
  {
    return $this->maybePayout();
  }

  public function onPlayerAfterConstruct($player, $event)
  {
    return $this->maybePayout();
  }

  public function onPlayerAfterFencing($player, $event)
  {
    return empty($event['newUsedSpaces']) ? null : $this->maybePayout();
  }

  public function onPlayerAfterStables($player, $event)
  {
    return empty($event['newUsedSpaces']) ? null : $this->maybePayout();
  }

  private function maybePayout()
  {
    $turnId = Globals::getTurnId() ?? '';
    if ($turnId === '') {
      return null;
    }

    if (($this->getExtraDatas('paidFor') ?? '') === $turnId) {
      return $this->flagCardNode();
    }

    Engine::insertAtRoot([
      'type' => NODE_SEQ,
      'childs' => [
        $this->gainNode([FOOD => 1]),
        $this->unflagCardNode(),
      ],
    ], true);

    $this->setExtraDatas('paidFor', $turnId);
    return $this->flagCardNode();
  }
}
