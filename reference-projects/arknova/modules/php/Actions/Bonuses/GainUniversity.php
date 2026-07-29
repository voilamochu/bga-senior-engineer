<?php

namespace ARK\Actions\Bonuses;

use ARK\Managers\Meeples;
use ARK\Managers\Players;
use ARK\Managers\ZooCards;
use ARK\Core\Notifications;
use ARK\Core\Stats;
use ARK\Helpers\Utils;
use ARK\Actions\Association;
use ARK\Models\Player;

class GainUniversity extends \ARK\Models\Action
{
  public function getState(): int
  {
    return ST_GAIN_UNIVERSITY;
  }

  public function argsGainUniversity($player = null)
  {
    $player = $player ?? Players::getActive();
    return ['meeples' => Association::getAvailableUniversitiesAux($player)];
  }

  public function getDescription(): string|array
  {
    return clienttranslate('Gain a university');
  }

  public function isDoable(Player $player): bool
  {
    return !empty($this->argsGainUniversity($player)['meeples']);
  }

  public function isOptional(): bool
  {
    $player = Players::getActive();
    return !$this->isDoable($player);
  }

  public function stGainUniversity()
  {
    if (empty($this->argsGainUniversity()['meeples'])) {
      $this->actPassGain(); // TODO
    }
  }

  public function actGainUniversity($univId)
  {
    self::checkAction('actGainUniversity');
    $player = Players::getActive();

    if (!in_array($univId, $this->argsGainUniversity()['meeples'])) {
      throw new \BgaVisibleSystemException('Invalid university. Should not happen');
    }

    $univ = Meeples::getSingle($univId);

    // MW
    if (in_array($univ['type'], UNIVERSITIES_ANIMALS)) {
      $genericUniv = Meeples::getAvailableUniversities()->filter(fn($m) => $m['type'] == UNIVERSITY_SCIENCE_ANIMAL_GEN)->first();
      if (!is_null($genericUniv) && $genericUniv['location'] != 'box') {
        Meeples::move($genericUniv['id'], 'box');
        Notifications::takeSpecializedUniv($genericUniv);
      }
    }

    $bonuses = $player->addUniversity($univId);
    $this->insertBonusesFlow($bonuses, \clienttranslate('university'));

    $this->resolveAction([$univId]);
  }
}
