<?php
namespace AGR\Cards\C;
use AGR\Core\Globals;
use AGR\Core\Engine;
use AGR\Core\Notifications;
use AGR\Managers\Meeples;

class C67_MineralFeeder extends \AGR\Models\MinorImprovement
{
  public function __construct($row)
  {
    parent::__construct($row);
    $this->id = 'C67_MineralFeeder';
    $this->name = clienttranslate('Mineral Feeder');
    $this->deck = 'C';
    $this->number = 67;
    $this->category = CROP_PROVIDER;
    $this->desc = [
      clienttranslate(
        'At the start of each round that does not end with a harvest, if you have at least 1 <SHEEP> in a pasture, you get 1 <GRAIN>.'
      ),
    ];
    $this->vp = 1;
    $this->cost = [
      REED => 1,
    ];
    $this->isCorbariusOrDulcinaria = true;
  }

  public function isListeningTo($event)
  {
    if (!$this->isPlayerEvent($event) || $event['type'] != 'StartOfTurn') {
      return false;
    }
    $harvestRounds = [4, 7, 9, 11, 13, 14];
    return !in_array(Globals::getTurn(), $harvestRounds);
  }

  public function hasSheepInPasture($player)
  {
    $zones = $player->board()->getAnimalsDropZonesWithAnimals();
    foreach ($zones as $zone) {
      if ($zone['type'] == 'pasture' && $zone[SHEEP] >= 1) {
        return true;
      }
    }
    return false;
  }

  public function hasPasture($player)
  {
    $zones = $player->board()->getAnimalsDropZonesWithAnimals();
    foreach ($zones as $zone) {
      if ($zone['type'] == 'pasture') {
        return true;
      }
    }
    return false;
  }

  public function onPlayerStartOfTurn($player, $event)
  {
    if ($this->hasSheepInPasture($player)) {
      return $this->gainNode([GRAIN => 1]);
    }

    // Offer a reorganize if the player has sheep and a pasture but no sheep in a pasture yet
    $sheepCount = $player->countAnimalsOnBoard()[SHEEP] ?? 0;
    if ($sheepCount > 0 && $this->hasPasture($player)) {
      return [
        'type' => NODE_SEQ,
        'childs' => [
          [
            'action' => REORGANIZE,
            'optional' => true,
            'pId' => $player->getId(),
            'desc' => '<REORGANIZE>',
          ],
          [
            'action' => SPECIAL_EFFECT,
            'args' => [
              'cardId' => $this->id,
              'method' => 'checkAndGainGrain',
            ],
          ],
        ],
      ];
    }
  }

  public function checkAndGainGrain()
  {
    $player = $this->getPlayer();
    if ($this->hasSheepInPasture($player)) {
      Engine::insertAsChild($this->gainNode([GRAIN => 1]));
    }
  }

  public function getCheckAndGainGrainDescription()
  {
    return [
      'log' => clienttranslate('Check ${card_name} for <GRAIN>'),
      'args' => [
        'card_name' => $this->name,
      ],
    ];
  }
}
