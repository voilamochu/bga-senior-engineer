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

class FreePersonSponsor extends \ARK\Models\Action
{
  public function getState(): int
  {
    return ST_FREE_PERSON_SPONSOR;
  }

  public function isOptional(): bool
  {
    return true;
  }

  public function getPlayableSponsors($player, $isDoableTesting = false)
  {
    $hand = $player->getHand(CARD_SPONSOR)->filter(fn($card) => $card->isPerson());

    $icons = $player->countCardIcons();
    $buyable = [];
    foreach ($hand as $cId => $sponsor) {
      // 1. Are conditions met?
      if (!$sponsor->canBePlayed($player, $icons)) {
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
    return clienttranslate('Play a person sponsor for free');
  }

  public function argsFreePersonSponsor()
  {
    $player = Players::getActive();
    $ctx = $this->getCtxArgs();

    return [
      '_private' => ['active' => ['cardIds' => $this->getPlayableSponsors($player)]],
    ];
  }

  public function actFreePersonSponsor($cardId)
  {
    self::checkAction('actFreePersonSponsor');
    $player = Players::getActive();
    $cards = $this->getPlayableSponsors($player);

    // 0. Sanity check
    if (!in_array($cardId, $cards)) {
      throw new \BgaVisibleSystemException('Invalid sponsor. Should not happen');
    }

    $sponsor = ZooCards::get($cardId);

    // 1. place sponsor card
    $sponsor->setPId($player->getId());
    $sponsor->setLocation('inPlay');
    Stats::incSponsorsPlayed($player);
    $meeples = [];
    for ($i = 0; $i < $sponsor->getNTokensToAdd(); $i++) {
      $meeples[] = Meeples::addTokenOnCard($player->getId(), $sponsor->getId(), $i);
    }
    Notifications::playSponsor($player, $sponsor, $meeples, false);

    // 2. Get bonuses
    $bonuses = $sponsor->getBonuses();
    $this->insertBonusesFlow($bonuses, '', null, $cardId);

    // 3. Execute immediate effect :
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
