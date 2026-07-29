<?php

namespace ARK\Models;

use ARK\Managers\Meeples;

class Sponsor extends ZooCard
{
  protected $type = \CARD_SPONSOR;
  protected array $staticAttributes = [
    ['supported', 'obj'],
    'type',
    'name',
    ['number', 'int'],
    ['lvl', 'int'],
    ['appeal', 'int'],
    ['conservation', 'int'],
    ['reputation', 'int'],
    ['enclosureRequirements', 'obj'],
    ['specialEnclosure', 'obj'],
    ['categories', 'obj'],
    ['prerequisites', 'obj'],
    ['continents', 'obj'],
    ['effects', 'obj'],
    ['wave', 'bool'],
    ['person', 'bool'],
  ];
  protected string $name;
  protected int $number;
  protected int $lvl;
  protected int $appeal;
  protected int $conservation;
  protected int $reputation;
  protected array $enclosureRequirements;
  protected array $specialEnclosure;
  protected array $categories;
  protected array $prerequisites;
  protected array $continents;
  protected array $effects;
  protected bool $wave;
  protected bool $person;

  protected $implemented = true;

  public function countIcon($icon)
  {
    return $this->getPlayer()->countCardIcon($icon);
  }

  public function scoreConservation($n = 1, $mapBonuses = null)
  {
    $player = $this->getPlayer();
    if (is_null($mapBonuses)) {
      if ($n > 0) {
        $player->incConservation($n, true, $this);
      }
    } else {
      $g = 0;
      foreach ($mapBonuses as $v => $gain) {
        if ($n >= $v) {
          $g = $gain;
        }
      }
      if ($g > 0) {
        $player->incConservation($g, true, $this);
      }
    }
  }

  public function getNTokensToAdd()
  {
    return 0;
  }

  public function getTokensOnIt()
  {
    return Meeples::getTokensOnCard($this->pId, $this->id);
  }

  public function getBonuses()
  {
    $bonuses = [];
    if ($this->getAppeal() > 0) {
      $bonuses[] = [APPEAL => $this->getAppeal()];
    }
    if ($this->getConservation() > 0) {
      $bonuses[] = [CONSERVATION => $this->getConservation()];
    }
    if ($this->getReputation() > 0) {
      $bonuses[] = [REPUTATION => $this->getReputation()];
    }
    return $bonuses;
  }

  public function getIcons()
  {
    return array_merge(
      array_count_values($this->getCategories()),
      array_count_values($this->getContinents()),
      $this->getEnclosureRequirements()
    );
  }

  public function getIncome()
  {
    return null;
  }

  public function getImmediate()
  {
    return null;
  }

  public function getPassive()
  {
    return [];
  }

  protected $listeningIcon = null;
  protected $listeningMode = MY_ZOO;
  protected $listeningBonuses = null;
  public function getIconsReaction($icons, $isOwnZoo)
  {
    // Must be listening to one icon
    if (is_null($this->listeningIcon)) {
      return [];
    }
    // If listening only to icons in my zoo, make sure it was added in my zoo
    if (!$isOwnZoo && $this->listeningMode == MY_ZOO) {
      return [];
    }
    // How many icons of that type ?
    $n = $icons[$this->listeningIcon] ?? 0;
    if ($n == 0) {
      return [];
    }

    // Now multiply the effect of each bonus by that multiplier
    $bonuses = [];
    foreach ($this->listeningBonuses as $bonus) {
      $bonus['pId'] = $this->pId;

      // Cant do easy multiplication for some sponsor
      if (in_array($this->id, ['S270_MarineResearchExpedition'])) {
        for ($i = 0; $i < $n; $i++) {
          $bonuses[] = $bonus;
        }
      }
      // General case : *$n
      else {
        $type = array_keys($bonus)[0];
        $bonus[$type] *= $n;
        $bonuses[] = $bonus;
      }
    }

    return $bonuses;
  }
}
