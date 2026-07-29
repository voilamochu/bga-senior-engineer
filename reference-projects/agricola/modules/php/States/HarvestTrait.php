<?php

namespace AGR\States;

use AGR\Core\Engine;
use AGR\Core\Globals;
use AGR\Core\Notifications;
use AGR\Helpers\Utils;
use AGR\Managers\Actions;
use AGR\Managers\Farmers;
use AGR\Managers\PlayerCards;
use AGR\Managers\Players;

trait HarvestTrait
{
  /****************************
   ****** Starting harvest *****
   ****************************/
  function stStartHarvest()
  {
    Notifications::startHarvest();

    Globals::setHarvest(true);
    Globals::setGameFlowPhase('harvest');
    Globals::setSkipHarvest(Globals::getPassHarvest());
    Globals::setPassHarvest([]);
    Globals::setExchangeFlags([]);

    // Some field phase cards want goods you can get from "anytime in harvest" cards. Let players make
    // any such exchanges first, but gate this tightly so it's not annoying
    $this->initCustomTurnOrder('harvestPrep', HARVEST, 'harvestPrepPlayer', 'stHarvestStartListeners', false, true);
  }

  function stHarvestStartListeners()
  {
    $this->checkCardListeners('StartHarvest', 'stStartHarvestFieldPhase', [], HARVEST);
  }

  function harvestPrepPlayer($args = [])
  {
    $player = Players::getActive();
    if (!$player->harvestPrepGoods()) {
      $this->nextPlayerCustomOrder('harvestPrep');
      return;
    }

    Engine::setup(['action' => EXCHANGE, 'pId' => $player->getId()], ['order' => 'harvestPrep']);
    Engine::proceed();
  }

  /****************************
   ********* Field phase *******
   ****************************/
  function stStartHarvestFieldPhase()
  {
    Globals::setSkipFieldAndBreed(Globals::getPassFieldAndBreed());
    Globals::setPassFieldAndBreed([]);
    Globals::setFieldPhase(true);
    Notifications::startHarvestField();
    $this->checkCardListeners('StartHarvestFieldPhase', 'stInitHarvestFieldPhase', [], HARVEST);
  }

  function stInitHarvestFieldPhase()
  {
    $this->initCustomTurnOrder('harvestField', HARVEST, ST_HARVEST_FIELD, 'stEndHarvestFieldPhase');
  }

  /**
   * Harvest growing crops
   */
  function stHarvestFieldPhase()
  {
    $player = Players::getActive();

    // Get reaction cards
    $event = [
      'type' => 'HarvestFieldPhase',
      'method' => 'HarvestFieldPhase',
      'pId' => $player->getId(),
    ];
    $reaction = PlayerCards::getReaction($event, false);

    // Insert default REAP node
    $crops = $player->board()->getHarvestCrops();
    if (count($crops) > 0) {
      $reaction['childs'][] = [
        'action' => REAP,
        'pId' => $player->getId(),
        'args' => [
          'trigger' => HARVEST,
        ],
      ];
    }

    if (empty($reaction['childs'])) {
      $this->nextPlayerCustomOrder('harvestField');
    } else {
      Engine::setup($reaction, ['order' => 'harvestField']);
      Engine::proceed();
    }
  }

  function stEndHarvestFieldPhase()
  {
    $this->checkCardListeners('EndHarvestFieldPhase', 'stStartHarvestFeedingPhase', [], \HARVEST);
  }

  /****************************
   ******* Feeding phase *******
   ****************************/
  function stStartHarvestFeedingPhase()
  {
    Globals::setFieldPhase(false);
    Notifications::startHarvestFeed();
    $this->checkCardListeners('StartHarvestFeedingPhase', 'stInitHarvestFeedingPhase', [], \HARVEST);
  }

  function stInitHarvestFeedingPhase()
  {
    $this->initCustomTurnOrder('harvestFeed', \HARVEST, ST_HARVEST_FEED, 'stEndHarvestFeedingPhase');
  }

  /**
   * Go to next player that needs to feed its family
   */
  function stHarvestFeed()
  {
    $player = Players::getActive();
    // Get triggered cards
    $event = [
      'type' => 'HarvestFeedingPhase',
      'method' => 'HarvestFeedingPhase',
      'pId' => $player->getId(),
    ];
    $reaction = PlayerCards::getReaction($event, false);

    $exchanges = $player->getPossibleExchanges(HARVEST, true);
    $cards = [];
    foreach ($exchanges as $exchange) {
      if (!is_null($exchange['flag'] ?? null)) {
        $card = PlayerCards::get($exchange['flag']);
        $card->setMarked(true);
      }
    }

    // Exchange node
    $costs = Utils::formatFee([FOOD => $player->getHarvestCost()]);
    if (Actions::isDoable(EXCHANGE, [], $player)) {
      // Do we have to enter pay ?
      $pref = $player->getPref(OPTION_AUTOPAY_HARVEST);
      $cantPay = !Actions::isDoable(PAY, ['costs' => $costs], $player);
      $hasSpecialExchange = Actions::isDoable(EXCHANGE, ['exclusive' => true], $player);
      $hasWhiskyDistiller = $player->hasPlayedCard('D106_WhiskyDistiller') && $player->countReserveResource(GRAIN) >= 1;
      $hasLumberVirtuoso = $player->hasPlayedCard('D129_LumberVirtuoso') && $player->countReserveResource(WOOD) >= 5;
      $hasSocialBenefits = $player->hasPlayedCard('D76_SocialBenefits');

      if ($pref != OPTION_AUTOPAY_HARVEST_ENABLED || $cantPay || $hasSpecialExchange || $hasWhiskyDistiller || $hasLumberVirtuoso || $hasSocialBenefits) {
        $reaction['childs'][] = [
          'action' => EXCHANGE,
          'reusable' => true,
          'pId' => $player->getId(),
        ];
      }
    } else {
      $hasAnytimeActions = !(PlayerCards::getReaction(
        [
          'type' => 'anytime',
          'method' => 'atAnytime',
          'action' => null,
          'pId' => $player->getId(),
        ],
        false
      ) == null);

      if ($hasAnytimeActions) {
        $reaction['childs'][] = [
          'action' => EXCHANGE,
          'reusable' => true,
          'pId' => $player->getId(),
        ];
      }
    }

    // Pay node
    $reaction['childs'][] = [
      'action' => PAY,
      'pId' => $player->getId(),
      'resolveParent' => true,
      'args' => [
        'costs' => $costs,
        'source' => clienttranslate('Harvest'),
        'harvest' => true,
      ],
    ];

    // Inserting into engine
    self::giveExtraTime($player->getId());
    Engine::setup($reaction, ['order' => 'harvestFeed']);
    Engine::proceed();
  }

  function stEndHarvestFeedingPhase()
  {
    $player = Players::getActive();
    $exchanges = $player->getPossibleExchanges(HARVEST, true);
    foreach ($exchanges as $exchange) {
      if (!is_null($exchange['flag'] ?? null)) {
        $card = PlayerCards::get($exchange['flag']);
        $card->setMarked(false);
      }
    }
    Globals::incCompletedFeedingPhases();

    $player = Players::getActive();
    if ($player->hasPlayedCard('A148_Woolgrower') || $player->hasPlayedCard('B86_TruffleSearcher')) {
      Notifications::updateDropZones($player);
    }

    $this->checkCardListeners('EndHarvestFeedingPhase', 'stHarvestPrepareBreed', [], \HARVEST);
  }

  /****************************
   ******* Breeding phase ******
   ****************************/
  function stHarvestPrepareBreed()
  {
    Notifications::startHarvestBreed();
    $this->initCustomTurnOrder('harvestBreed', HARVEST, ST_HARVEST_BREED, 'stHarvestEnd');
  }

  /**
   * Go to next player that needs to feed its family
   */
  function stHarvestBreed()
  {
    Globals::setBreedPhase(true);
    $player = Players::getActive();
    // Listen for cards enforcing reorganization on last harvest (eg Organic Farmer)
    $enforceReorganize = false;
    if (Globals::getTurn() == 14) {
      foreach ($player->getCards(null, true) as $card) {
        $enforceReorganize = $enforceReorganize || $card->enforceReorganizeOnLastHarvest();
      }
    }

    // If player has enough to breed, creation of a baby in reserve
    $created = $player->breed();
    if (!$created && !$enforceReorganize) {
      $this->nextPlayerCustomOrder('harvestBreed');
      return;
    }

    // Inserting leaf REORGANIZE
    Engine::setup(
      [
        'pId' => $player->getId(),
        'action' => REORGANIZE,
        'args' => [
          'trigger' => HARVEST,
        ],
      ],
      ['order' => 'harvestBreed']
    );
    Engine::proceed();
  }

  /****************************
   ******* Ending harvest ******
   ****************************/
  function stHarvestEnd()
  {
    Globals::setBreedPhase(false);
    $this->checkCardListeners('EndHarvest', 'stAfterHarvest');
  }

  function stAfterHarvest()
  {
    // adding extra return home event for triggered growths onto cards during harvest (e.g. A21)
    Players::returnHome();
    Notifications::returnHomeSilent(Farmers::getAllAvailable());
    $this->checkCardListeners('AfterHarvest', ST_END_OF_TURN, [], \HARVEST);
  }
}
