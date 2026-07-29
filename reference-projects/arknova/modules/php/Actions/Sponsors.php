<?php

namespace ARK\Actions;

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

class Sponsors extends \ARK\Models\Action
{
  public function getState(): int
  {
    return ST_SPONSORS;
  }

  public function getDescription(): array
  {
    $money = $this->getCtxArg('canBreakForMoney') ?? 0;
    return [
      'log' =>
      $this->getLevel() == 1
        ? ($money > 0 ?
          clienttranslate('Play 1 sponsor card of max level <STRENGTH:${n}> or <BREAK:${break}>:<MONEY:${money}>') :
          clienttranslate('Play 1 sponsor card of max level <STRENGTH:${n}>')
        )
        : ($money > 0 ?
          clienttranslate('Play 1 or more sponsor cards of max total level <STRENGTH:${n}> or <BREAK:${break}>:<MONEY:${money}>') :
          clienttranslate('Play 1 or more sponsor cards of max total level <STRENGTH:${n}>')
        ),
      'args' => [
        'n' => $this->getStrength(),
        'break' => $money / $this->getLevel(),
        'money' => $money,
      ],
    ];
  }

  public function canBreakForMoney()
  {
    return !is_null($this->getCtxArg('canBreakForMoney'));
  }

  public function isDoable(Player $player): bool
  {
    if ($this->canBreakForMoney()) {
      return true;
    }

    // no sponsor with less than
    $lvlReduction = $player->canUseMap(8) ? 1 : 0;
    if ($this->getStrength() + $lvlReduction < 3) {
      return false;
    }
    return $this->getPlayableSponsors($player, true);
  }

  public function isOptional(): bool
  {
    return !empty($this->getPreviousActions());
  }

  public function getPreviousActions()
  {
    return $this->getCtxArg('previous') ?? [];
  }

  public function getStrengthLeft()
  {
    $strength = $this->getStrength();
    foreach ($this->getPreviousActions() as $s) {
      $strength -= $s;
    }
    return $strength;
  }

  public function getPlayableSponsors($player, $isDoableTesting = false)
  {
    $strength = $this->getStrengthLeft();
    $hand = $player->getHand(CARD_SPONSOR);
    if ($this->isUpgraded()) {
      $hand = $hand->merge($player->getCardsInReputationRange(CARD_SPONSOR));
    }

    $icons = $player->countCardIcons();
    $lvlReduction = $player->canUseMap(8) ? 1 : 0;
    $buyable = [];
    foreach ($hand as $cId => $sponsor) {
      // 1. Are conditions met?
      if (!$sponsor->canBePlayed($player, $icons)) {
        continue;
      }

      // Is the strength big enough ?
      if ($sponsor->getLvl() - $lvlReduction > $strength) {
        continue;
      }

      // If card is on display, can we afford it ?
      $poolNumber = $sponsor->getPoolNumber();
      $fromDisplay = !is_null($poolNumber);
      if ($fromDisplay && $player->getMoney() < $poolNumber) {
        continue;
      }

      if ($isDoableTesting) {
        return true;
      }
      $buyable[] = $sponsor->getId();
    }

    return $isDoableTesting ? false : $buyable;
  }

  public function stSponsors() {}

  public function argsSponsors()
  {
    $player = Players::getActive();

    return [
      'i18n' => ['source'],
      '_private' => ['active' => ['cardIds' => $this->getPlayableSponsors($player)]],
      'showFolderCosts' => $this->isUpgraded(),
      'canBreakForMoney' => $this->getCtxArg('canBreakForMoney'),
      'lvl' => $this->getLevel(),

      // Title
      'source' => $this->isUpgraded() ? clienttranslate('hand or within reputation range') : clienttranslate('hand'),
      'strengthLeft' => $this->getStrengthLeft(),
      'strength' => $this->getStrength(),
      'strength_icon' => '',
      'descSuffix' => $this->canBreakForMoney() ? 'canBreakForMoney' : '',
    ];
  }

  public function actBreakForMoney()
  {
    self::checkAction('actBreakForMoney');

    $money = $this->getCtxArg('canBreakForMoney');
    $strength = $money / $this->getLevel();
    $this->insertAsChild([
      'type' => NODE_SEQ,
      'childs' => [['action' => ADVANCE_BREAK, 'args' => ['n' => $strength]], ['action' => GAIN, 'args' => [MONEY => $money]]],
    ]);

    // SPONSORS 4 - LVL2
    if ($this->getNumber() == 4 && $this->isUpgraded()) {
      $this->insertAsChild([
        'action' => SPONSORS_DISCARD_CARD_GET_BONUS,
        'args' => [
          'number' => $this->getNumber(),
          'lvl' => $this->getLevel(),
        ]
      ]);
    }

    $this->resolveAction(['money']);
  }

  public function actSponsors($cardId)
  {
    self::checkAction('actSponsors');
    $player = Players::getActive();
    $cards = $this->getPlayableSponsors($player);

    // 1. Sanity check
    if (!in_array($cardId, $cards)) {
      throw new \BgaVisibleSystemException('Invalid sponsor. Should not happen');
    }

    $sponsor = ZooCards::get($cardId);
    $poolNumber = $sponsor->getPoolNumber();
    $fromDisplay = !is_null($poolNumber);

    // 2. place sponsor card
    $sponsor->setPId($player->getId());
    $sponsor->setLocation('inPlay');
    Stats::incSponsorsPlayed($player);
    $meeples = [];
    for ($i = 0; $i < $sponsor->getNTokensToAdd(); $i++) {
      $meeples[] = Meeples::addTokenOnCard($player->getId(), $sponsor->getId(), $i);
    }
    Notifications::playSponsor($player, $sponsor, $meeples, $fromDisplay);

    // 2.bis) pay for it
    if ($fromDisplay) {
      $player->pay($poolNumber, true, \clienttranslate('playing sponsor from reputation range'));
      Stats::incMoneyUsedFromDisplay($player, $poolNumber);
    }

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

    if ($this->isUpgraded()) {
      $actions = $this->getPreviousActions();
      $lvlReduction = $player->canUseMap(8) ? 1 : 0;
      $actions[] = $sponsor->getLvl() - $lvlReduction;
      $this->duplicateAction(['previous' => $actions, 'canBreakForMoney' => null]);


      // SPECIAL CASE FOR SPONSORS3-II
      if ($this->getNumber() == 3) {
        foreach ($this->ctx->getParent()->getParent()->getChilds() as $node) {
          if ($node->getAction() == SPONSORS_DISCARD_CARD_GET_BONUS && !$node->isActionResolved()) {
            $found = true;
            $args = $node->getArgs();
            $args['suffix'] = '3-2bis';
            $node->replace(Engine::buildTree([
              'action' => SPONSORS_DISCARD_CARD_GET_BONUS,
              'args' => $args,
            ]));
            Engine::save();
          }
        }
      }
    }


    $this->resolveAction(['cardId' => $cardId]);
  }
}
