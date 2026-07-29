<?php
namespace AGR\Cards\A;
use AGR\Core\Engine;
use AGR\Core\Notifications;
use AGR\Managers\Players;

class A39_Chapel extends \AGR\Models\PlayerActionCard
{
  protected $type = MINOR;
  public function __construct($row)
  {
    parent::__construct($row);
    $this->id = 'A39_Chapel';
    $this->name = clienttranslate('Chapel');
    $this->deck = 'A';
    $this->number = 39;
    $this->category = POINTS_PROVIDER;
    $this->desc = [
      clienttranslate(
        'This is an action space for all. A player who uses it gets 3 bonus <SCORE>. If another player uses it, they must first pay you 1 <GRAIN>.'
      ),
    ];
    $this->extraVp = true;
    $this->vp = 3;
    $this->cost = [
      WOOD => '3',
      CLAY => '2',
    ];
    $this->prerequisite = clienttranslate('2 Occupations');
    $this->occupationPrerequisites = ['min' => 2];
    $this->isArtifexOrBubulcus = true;
    $this->flow = [
      'action' => SPECIAL_EFFECT,
      'args' => [
        'method' => 'activate',
        'cardId' => $this->id,
        'args' => [],
      ],
    ];
    $this->bannedStrong1or2p = true;
    $this->bannedStrong3or4p = true;
  }

  public function activate()
  {
    $player = Players::getActive();
    $pId = $player->getId();
    $owner = $this->getPlayer()->getId();
    $this->setExtraDatas($pId, $this->getExtraDatas($pId) + 3);

    // Points are scored per-player via computeSpecialScore; this is just the +3 animation/log.
    // Owner's tokens land on the card badge (its running total), guests' on their own panel score.
    $location = $pId == $owner ? $this->id : 'panelScore';
    $meeples = [];
    for ($i = 0; $i < 3; $i++) {
      $meeples[] = ['type' => SCORE, 'location' => $location, 'pId' => $pId, 'destroy' => true];
    }
    Notifications::gainResources($player, $meeples, $this->id, $this->name);

    if ($pId != $owner) {
      Engine::insertAsChild($this->payNode([GRAIN => 1], null, 1, $owner));
    }
  }

  public function onBuy($player) {
    foreach (Players::getAll() as $player2) {
      $this->setExtraDatas($player2->getId(),0);
    }
  }

  public function computeSpecialScore($scores)
  {
    foreach ($scores as $pId => $score) {
      $this->addBonusScoringEntry($this->getExtraDatas($pId), null, Players::get($pId));
    }
  }

  public function jsonSerialize()
  {
    $data = parent::jsonSerialize();
    // Badge shows the owner's banked total so it survives reloads; scoring still reads extraDatas.
    if ($this->isPlayed()) {
      $owner = $this->getExtraDatas($this->getPlayer()->getId()) ?? 0;
      $data['bonusVp'] = $owner > 0 ? $owner : '';
    }
    return $data;
  }
}
