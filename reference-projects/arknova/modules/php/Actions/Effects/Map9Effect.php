<?php

namespace ARK\Actions\Effects;

use ARK\Managers\Players;
use ARK\Managers\ZooCards;
use ARK\Managers\Meeples;
use ARK\Core\Notifications;
use ARK\Core\Globals;
use ARK\Helpers\Utils;
use ARK\Helpers\Collection;
use ARK\Models\Player;

class Map9Effect extends \ARK\Models\Action
{
  public function getState(): int
  {
    return ST_MAP9;
  }

  public function getDescription(): string|array
  {
    $continent = $this->getContinent();
    return is_null($continent)
      ? clienttranslate('Remove one continent marker and gain 1 bonus')
      : [
        'log' => clienttranslate('Remove ${continent} marker and gain 1 bonus'),
        'args' => [
          'continent' => '<' . mb_strtoupper($continent) . '>',
        ],
      ];
  }

  public function getContinent()
  {
    return $this->getCtxArg('continent');
  }

  public function stMap9Effect()
  {
    $args = $this->argsMap9Effect();
    if (count($args['continents']) == 1) {
      $this->actMap9($args['continents'][0], true);
    } elseif (empty($args['continents'])) {
      $this->resolveAction([]);
    }
  }

  public function getContinentsLeft($player = null)
  {
    $continents = [];
    $player = $player ?? Players::getActive();
    foreach (CONTINENTS as $continent) {
      if (!is_null(Meeples::getTokenOnContinentArea($player->getId(), $continent))) {
        $continents[] = $continent;
      }
    }
    return $continents;
  }

  public function argsMap9Effect()
  {
    $continent = $this->getContinent();
    return [
      'continents' => is_null($continent) ? $this->getContinentsLeft() : [$continent],
    ];
  }

  public function actMap9($continent, $auto = false)
  {
    self::checkAction('actMap9', $auto);
    $args = $this->argsMap9Effect();
    if (!in_array($continent, $args['continents'])) {
      throw new \BgaVisibleSystemException('Wrong continent. Should not happen');
    }

    $player = Players::getActive();
    $meeple = Meeples::getTokenOnContinentArea($player->getId(), $continent);
    Meeples::destroy($meeple['id']);
    Notifications::removeContinentMarker($player, $continent, $meeple);

    if (empty($this->getContinentsLeft())) {
      $this->pushParallelChild([
        'action' => GAIN,
        'args' => [CONSERVATION => 1],
        'source' => clienttranslate('Map 9 effect'),
      ]);
    }

    $this->pushParallelChild([
      'type' => NODE_XOR,
      'childs' => [
        [
          'action' => TAKE_BONUS,
          'args' => [
            'type' => REPUTATION,
            'n' => 1,
            'sourceType' => 'bonusTile',
            'source' => clienttranslate('Map 9 effect'),
          ],
        ],
        [
          'action' => TAKE_BONUS,
          'args' => [
            'type' => MONEY,
            'n' => 4,
            'sourceType' => 'bonusTile',
            'source' => clienttranslate('Map 9 effect'),
          ],
        ],
        [
          'action' => TAKE_BONUS,
          'args' => [
            'type' => APPEAL,
            'n' => 2,
            'sourceType' => 'bonusTile',
            'source' => clienttranslate('Map 9 effect'),
          ],
        ],
        [
          'action' => TAKE_BONUS,
          'args' => [
            'type' => CLEVER,
            'n' => 1,
            'sourceType' => 'bonusTile',
            'source' => clienttranslate('Map 9 effect'),
          ],
        ],
        [
          'action' => TAKE_BONUS,
          'args' => [
            'type' => KIOSK_OR_PAVILION,
            'n' => 1,
            'sourceType' => 'bonusTile',
            'source' => clienttranslate('Map 9 effect'),
          ],
        ],
      ],
    ]);
    $this->resolveAction(['continent' => $continent]);
  }
}
