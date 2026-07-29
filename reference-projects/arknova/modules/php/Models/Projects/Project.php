<?php

namespace ARK\Models\Projects;

use ARK\Managers\ZooCards;
use ARK\Managers\Meeples;
use ARK\Core\Notifications;
use ARK\Managers\Players;

class Project extends \ARK\Models\ZooCard
{
  protected $type = \CARD_PROJECT;
  protected array $staticAttributes = [
    ['supported', 'obj'],
    'type',
    'name',
    ['number', 'int'],
    'desc',
    'icon',
    'category', // breeding/release/etc.
    ['slots', 'obj'], // 0 => [condition => [...] (for icons), gain => [...] ]
    ['wave', 'bool'],
    ['playedBonus', 'obj'],
    'asset',
  ];
  protected string $name;
  protected int $number;
  protected string $desc;
  protected string $icon;
  protected string $category;
  protected array $slots = [];
  protected bool $wave;
  protected array $projectType = [];
  protected array $playedBonus = [];
  protected string $asset = "";

  public function getPlayableSlots($player)
  {
    // you can support a project only once unless you have S224_MigrationRecording
    if ($player->countCardTokens($this->id) != 0) {
      if (!$player->hasPlayedCard('S224_MigrationRecording') || ($this->category ?? null) != PROJECT_RELEASE) {
        return [];
      }
    }

    $bonusIcon = $player->getIconBonusForBaseProjects();
    $possible = [];
    foreach ($this->getEmptySlots() as $sId) {
      $slot = $this->slots[$sId];
      $isPlayable = $this->canPlaySlot($player, $slot, $bonusIcon);

      // True => add the slot
      if ($isPlayable === true) {
        $possible[] = $sId;
      }
      // Array => ids of animals
      elseif (is_array($isPlayable) && $this->category == 'release') {
        $possible[] = ['id' => $sId, 'animalIds' => $isPlayable];
      }
    }

    return $possible;
  }

  public function getEmptySlots()
  {
    $takenSlots = Meeples::getTokensOnCard(null, $this->id)
      ->map(function ($meeple) {
        $t = explode('_', $meeple['location']);
        return (int) $t[count($t) - 1];
      })
      ->toArray();

    return array_diff(array_keys($this->slots), $takenSlots);
  }

  public function getBonus($sId)
  {
    return $this->slots[$sId]['flow'];
  }
}
