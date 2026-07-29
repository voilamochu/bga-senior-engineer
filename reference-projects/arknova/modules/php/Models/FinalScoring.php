<?php

namespace ARK\Models;

use ARK\Managers\ZooCards;
use ARK\Managers\Meeples;
use ARK\Managers\Notifications;
use ARK\Managers\Players;

class FinalScoring extends ZooCard
{
  protected $type = \CARD_SCORING;
  protected array $staticAttributes = [
    ['supported', 'obj'],
    'type',
    'name',
    ['number', 'int'],
    'desc',
    'icon',
    ['scoreMap', 'obj'],
    ['wave', 'bool'],
    'asset',
  ];
  protected int $number;
  protected string $desc;
  protected string $icon;
  protected string $name;
  protected string $asset = "";
  protected ?array $scoreMap = null;

  public function getQuantity()
  {
    return 0;
  }

  public function getScoreBonus()
  {
    $qty = $this->getQuantity();
    $bonus = 0;
    foreach ($this->scoreMap ?? [] as $threshold => $b) {
      if ($qty >= $threshold) {
        $bonus = $b;
      }
    }
    if (is_null($this->scoreMap)) {
      $bonus = $qty;
    }

    if ($bonus != 0) {
      return [CONSERVATION => $bonus];
    } else {
      return null;
    }
  }
}
