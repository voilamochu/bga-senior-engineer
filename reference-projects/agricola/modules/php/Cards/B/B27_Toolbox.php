<?php

namespace AGR\Cards\B;

use AGR\Models\MinorImprovement;
use AGR\Helpers\Utils;
use AGR\Helpers\CardRulings;
use AGR\Core\Globals;

class B27_Toolbox extends MinorImprovement
{
  public function __construct($row)
  {
    parent::__construct($row);
    $this->id = 'B27_Toolbox';
    $this->name = clienttranslate('Toolbox');
    $this->deck = 'B';
    $this->number = 27;
    $this->category = ACTIONS_BOOSTER;
    $this->desc = [
      clienttranslate(
        'In the work phase, after each turn in which you build at least 1 room, stable, or fence, you can build the __Joinery__, __Pottery__, or __Basketmaker\'s Workshop__ major improvement.'
      ),
    ];
    $this->cost = [
      WOOD => '1',
    ];
    $this->isArtifexOrBubulcus = true;

    $this->rulings = array_merge(
      CardRulings::fromKeys([
        'OFFTURN_STABLES_DONT_TRIGGER',
      ]),
      [
        clienttranslate('Pay the cost of the major improvement normally.'),
      ]
    );
  }

  public function onBuy($player)
  {
    if (
      Globals::isWorkPhase() &&
      (
        $this->numStablesBuiltThisTurn($player) > 0 ||
        $this->hasBuiltFencesThisTurn($player) ||
        !is_null($this->roomTypeBuiltThisTurn($player))
      )
    ) {
      return $this->flagCardNode();
    }

    return $this->unflagCardNode();
  }

  public function isListeningTo($event)
  {
    if (!Globals::isWorkPhase()) {
      return false;
    }

    if (($event['method'] ?? '') === 'AfterPlaceFarmer' && $this->isActionEvent($event, 'PlaceFarmer', null)) {
      return true;
    }

    if (!$this->isPlayerEvent($event)) {
      return false;
    }

    return $this->isActionEvent($event, 'Stables')
      || $this->isActionEvent($event, 'Construct')
      || ($this->isActionEvent($event, 'Fencing') && isset($event['fences']) && count($event['fences']) >= 1);
  }

  public function onPlayerAfterStables($player, $event)
  {
    if ($this->numStablesBuiltThisTurn($player) <= 0) {
      return null;
    }
    return $this->flagCardNode();
  }

  public function onPlayerAfterConstruct($player, $event)
  {
    if (is_null($this->roomTypeBuiltThisTurn($player))) {
      return null;
    }
    return $this->flagCardNode();
  }

  public function onPlayerAfterFencing($player, $event)
  {
    return $this->flagCardNode();
  }

  public function onPlayerAfterPlaceFarmer($player, $args)
  {
    return $this->getToolboxAfterTurnFlow();
  }

  public function onOpponentAfterPlaceFarmer($player, $args)
  {
    return $this->getToolboxAfterTurnFlow();
  }

  protected function getToolboxAfterTurnFlow()
  {
    if (!$this->isFlagged()) {
      return null;
    }

    return [
      'type' => NODE_SEQ,
      'pId' => $this->pId,
      'childs' => [
        Utils::wrapOptional([
          'action' => IMPROVEMENT,
          'pId' => $this->pId,
          'args' => [
            'types' => [MAJOR],
            'allowedPurchases' => ['Major_Basket', 'Major_Joinery', 'Major_Pottery'],
            'trueAction' => false,
          ],
        ]),
        $this->unflagCardNode(),
      ],
    ];
  }
}
