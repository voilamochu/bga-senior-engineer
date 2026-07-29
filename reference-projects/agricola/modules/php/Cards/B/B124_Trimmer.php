<?php

namespace AGR\Cards\B;

use AGR\Models\Occupation;
use AGR\Core\Globals;
use AGR\Managers\Players;

class B124_Trimmer extends Occupation
{
  public function __construct($row)
  {
    parent::__construct($row);
    $this->id = 'B124_Trimmer';
    $this->name = clienttranslate('Trimmer');
    $this->deck = 'B';
    $this->number = 124;
    $this->category = BUILDING_RESOURCE_PROVIDER;
    $this->desc = [
      clienttranslate(
        'In each work phase, after you enclose at least one farmyard space, you get 2 <STONE>. (Subdividing an existing pasture does not count.)'
      ),
    ];
    $this->players = '1+';
    $this->isArtifexOrBubulcus = true;
  }

  public function isListeningTo($event)
  {
    return (!$this->isFlagged() && $this->isActionEvent($event, 'Fencing') && Globals::getWorkPhase()) ||
      ($this->isPlayerEvent($event) && $event['type'] == 'startOfWork') ||
      ($this->isPlayerEvent($event) && $event['type'] == 'ReturnHome');
  }

  public function onBuy($player)
  {
    if (Globals::getWorkPhase()) {
      return [
        'type' => NODE_SEQ,
        'childs' => [
          $this->updateFencingAreaNode($player),
          $this->unflagCardNode(),
        ],
      ];
    } else {
      return $this->updateFencingAreaNode($player);
    }
  }

  public function onPlayerStartOfWork($player, $event)
  {
    return $this->unflagCardNode();
  }

  public function onPlayerReturnHome($player, $event)
  {
    return $this->flagCardNode();
  }

  public function onPlayerAfterFencing($player, $event)
  {
    $covered = $player->board()->countCoveredZonesByPastures();
    if ($covered > $this->getExtraDatas('Farm')) {
      return [
        'type' => NODE_SEQ,
        'childs' => [
          $this->updateFencingAreaNode($player),
          $this->gainNode([STONE => 2]),
        ],
      ];
    } else {
      return $this->updateFencingAreaNode($player);
    }
  }

  function updateFencingAreaNode($player)
  {
    return [
      'action' => SPECIAL_EFFECT,
      'args' => [
        'cardId' => $this->id,
        'method' => 'updateFencingArea',
        'args' => [$player->getId()],
      ]
    ];
  }

  function updateFencingArea($pId)
  {
    $player = Players::get($pId);
    $covered = $player->board()->countCoveredZonesByPastures();
    $this->setExtraDatas('Farm', $covered);
  }
}
