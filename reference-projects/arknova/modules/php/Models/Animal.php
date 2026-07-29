<?php

namespace ARK\Models;

use ARK\Managers\Meeples;
use ARK\Core\Engine;
use ARK\Core\Globals;

class Animal extends ZooCard
{
  protected $type = \CARD_ANIMAL;
  protected array $staticAttributes = [
    ['supported', 'obj'],
    'type',
    'name',
    'latin',
    ['number', 'int'],
    ['cost', 'int'],
    ['appeal', 'int'],
    ['conservation', 'int'],
    ['reputation', 'int'],
    ['enclosureSize', 'int'],
    ['enclosureRequirements', 'obj'],
    ['specialEnclosure', 'obj'],
    ['categories', 'obj'],
    ['prerequisites', 'obj'],
    ['continents', 'obj'],
    ['ability', 'obj'],
    ['soloAbility', 'obj'],
    ['wave', 'bool'],
    ['reefAbility', 'obj'],
    ['noRegularEnclosure', 'bool']
  ];
  protected string $name;
  protected string $latin;
  protected int $number;
  protected int $cost;
  protected int $appeal;
  protected int $conservation;
  protected int $reputation;
  protected int $enclosureSize;
  protected array $enclosureRequirements;
  protected array $specialEnclosure;
  protected array $categories;
  protected array $prerequisites;
  protected array $ability;
  protected array $reefAbility;
  protected bool $noRegularEnclosure;
  protected array $soloAbility;

  public function getBonuses()
  {
    return [
      'appeal' => $this->getAppeal(),
      'conservation' => $this->getConservation(),
      'reputation' => $this->getReputation(),
    ];
  }

  public function getIcons()
  {
    return array_merge(
      array_count_values($this->getCategories()),
      array_count_values($this->getContinents()),
      $this->getEnclosureRequirements()
    );
  }

  public function getBuyCost($player)
  {
    $cost = parent::getBuyCost($player);
    if ($player->hasPlayedCard('S229_ExpertInSmallAnimals') && $this->isSmall()) {
      $cost -= 3;
    }
    if ($player->hasPlayedCard('S230_ExpertInLargeAnimals') && $this->isLarge()) {
      $cost -= 4;
    }

    return max($cost, 0);
  }

  public function checkConditions($player, $icons, $nCanIgnore = 0)
  {
    if ($player->hasPlayedCard('S263_WazaLargeAnimalProgram') && $this->isLarge()) {
      $nCanIgnore++;
    }
    if ($player->canUseMap(6)) {
      $nCanIgnore++;
    }
    // MW : Camouflage
    $nCanIgnore += Globals::getEffectCamouflage();
    // MW : bonus tile
    if ($player->hasKeptBonusTile(BONUS_IGNORE_CONDITION)) {
      $nCanIgnore += 3;
    }

    return parent::checkConditions($player, $icons, $nCanIgnore);
  }

  public function getContinent()
  {
    return $this->getContinents()[0] ?? null;
  }

  public function isSmall()
  {
    return $this->getEnclosureSize() <= 2;
  }

  public function isLarge()
  {
    return $this->getEnclosureSize() >= 4;
  }

  public function getSoloAbility()
  {
    if (parent::getSoloAbility() == []) {
      return $this->getAbility();
    }
    return parent::getSoloAbility();
  }

  /******** POWER  *********/
  public function getFlockSize()
  {
    foreach ($this->getAbility() as $ab => $n) {
      if ($ab == \FLOCK_ANIMAL) {
        return $n;
      }
    }
    return false;
  }

  public function getInventiveTokens()
  {
    return 1;
  }

  /************ MARK ***************/
  public function isMarked($ignorePId = null): bool
  {
    if (!$this->isInPool()) {
      return false;
    }

    $mark = $this->getMark();
    if (is_null($mark)) {
      return false;
    }

    if (!is_null($ignorePId) && $mark['pId'] == $ignorePId) {
      return false;
    }

    return true;
  }

  public function removeMarkForMoney($pId)
  {
    $token = $this->getMark();
    Meeples::removeFromCard($this->id);

    return [
      'action' => GAIN_MARKED,
      'args' => [
        'token' => $token
      ],
      'sourceId' => $this->id,
    ];
  }
}
