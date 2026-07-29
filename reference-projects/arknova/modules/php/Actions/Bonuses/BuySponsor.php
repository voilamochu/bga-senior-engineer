<?php

namespace ARK\Actions\Bonuses;

use ARK\Managers\Meeples;
use ARK\Managers\Players;
use ARK\Core\Notifications;
use ARK\Managers\ZooCards;
use ARK\Core\Engine;
use ARK\Helpers\FlowConvertor;
use ARK\Core\Stats;
use ARK\Helpers\Utils;
use ARK\Core\Globals;
use ARK\Models\Player;

class BuySponsor extends \ARK\Models\Action
{
  public function getState(): int
  {
    return ST_BUY_SPONSOR;
  }

  public function isOptional(): bool
  {
    return true;
  }

  public static function getPlayableSponsors($player, $isDoableTesting = false): bool|array
  {
    $hand = $player->getHand(CARD_SPONSOR);

    $icons = $player->countCardIcons();
    $lvlReduction = $player->canUseMap(8) ? 1 : 0;
    $buyable = [];
    foreach ($hand as $cId => $sponsor) {
      // 1. Are conditions met?
      if (!$sponsor->canBePlayed($player, $icons)) {
        continue;
      }

      // 2. Enough money ?
      if ($sponsor->getLvl() - $lvlReduction > $player->getMoney()) {
        continue;
      }

      if ($isDoableTesting) {
        return true;
      }
      $buyable[] = $sponsor->getId();
    }

    return $isDoableTesting ? false : $buyable;
  }

  public function isDoable(Player $player): bool
  {
    return $this->getPlayableSponsors($player, true);
  }

  public function getDescription(): string|array
  {
    return clienttranslate('Play sponsor with money');
  }

  public function argsBuySponsor()
  {
    $player = Players::getActive();
    $ctx = $this->getCtxArgs();

    return [
      '_private' => ['active' => ['cardIds' => $this->getPlayableSponsors($player)]],
    ];
  }

  public function actBuySponsor($cardId)
  {
    self::checkAction('actBuySponsor');
    $player = Players::getActive();
    $cards = $this->getPlayableSponsors($player);

    // 0. Sanity check
    if (!in_array($cardId, $cards)) {
      throw new \BgaVisibleSystemException('Invalid sponsor. Should not happen');
    }

    $sponsor = ZooCards::get($cardId);

    // 1. pay for it
    $lvlReduction = $player->canUseMap(8) ? 1 : 0;
    $player->pay($sponsor->getLvl() - $lvlReduction, true, clienttranslate('buying sponsor card'));
    // 1.bis OKAPI STABLE
    if ($this->ctx->getSourceId() == 'S253_OkapiStable') {
      $card = ZooCards::getSingle('S253_OkapiStable');
      $token = $card->getTokensOnIt()->first();
      Meeples::destroy($token['id']);
      Notifications::discardToken($player, '', $token, true);
    }

    // 2. place sponsor card
    $sponsor->setPId($player->getId());
    $sponsor->setLocation('inPlay');
    Stats::incSponsorsPlayed($player);
    $meeples = [];
    for ($i = 0; $i < $sponsor->getNTokensToAdd(); $i++) {
      $meeples[] = Meeples::addTokenOnCard($player->getId(), $sponsor->getId(), $i);
    }
    Notifications::playSponsor($player, $sponsor, $meeples, false);

    // 3. Get bonuses
    $bonuses = $sponsor->getBonuses();
    $this->insertBonusesFlow($bonuses, '', null, $cardId);

    // 4. Execute immediate effect :
    //  - after finishing effects are inserted in a special engine node
    //  - other effect are inserted as child
    $bonuses = $sponsor->getImmediate();
    $this->insertBonusesFlow($bonuses, '', null, $cardId);

    // if we are in income phase & we just added a sponsor with income
    if (Globals::isBreak() && !is_null($sponsor->getIncome())) {
      Engine::insertOrUpdateParallelChilds(
        [
          [
            'action' => ACTIVATE_CARD,
            'pId' => $player->getId(),
            'args' => [
              'cardId' => $sponsor->getId(),
              'event' => ['method' => 'getIncome'],
            ],
          ],
        ],
        Engine::$tree
      );
    }

    // Check for listeners
    $this->checkIconsListeners($sponsor->getIcons(), $player);

    $this->resolveAction(['cardId' => $cardId]);
  }
}
