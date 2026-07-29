/**
 *------
 * BGA framework: © Gregory Isabelli <gisabelli@boardgamearena.com> & Emmanuel Colin <ecolin@boardgamearena.com>
 * agricola implementation : © Timothée Pecatte <tim.pecatte@gmail.com>, Vincent Toper <vincent.toper@gmail.com>
 *
 * This code has been produced on the BGA studio platform for use on http://boardgamearena.com.
 * See http://en.boardgamearena.com/#!doc/Studio for more information.
 * -----
 *
 * agricola.js
 *
 * agricola user interface script
 *
 * In this file, you are describing the logic of your user interface, in Javascript language.
 *
 */

var isDebug = window.location.host == 'studio.boardgamearena.com' || window.location.hash.indexOf('debug') > -1;
var debug = isDebug ? console.info.bind(window.console) : function () {};

define([
  'dojo',
  'dojo/_base/declare',
  g_gamethemeurl + 'modules/js/vendor/nouislider.min.js',
  g_gamethemeurl + 'modules/js/vendor/sortable.min.js',
  'ebg/core/gamegui',
  'ebg/counter',
  g_gamethemeurl + 'modules/js/Core/game.js',
  g_gamethemeurl + 'modules/js/Core/modal.js',
  g_gamethemeurl + 'modules/js/ActionCards.js',
  g_gamethemeurl + 'modules/js/Players.js',
  g_gamethemeurl + 'modules/js/PlayerBoard.js',
  g_gamethemeurl + 'modules/js/Meeples.js',
  g_gamethemeurl + 'modules/js/Cards.js',
  g_gamethemeurl + 'modules/js/States/Sow.js',
  g_gamethemeurl + 'modules/js/States/ReorganizeAnimals.js',
  g_gamethemeurl + 'modules/js/States/Exchange.js',
  g_gamethemeurl + 'modules/js/States/SpecialEffect.js',
  g_gamethemeurl + 'modules/js/GameFlow.js',
  g_gamethemeurl + 'modules/js/Campaign.js',
], function (dojo, declare, noUiSlider, sortable) {
  const COLORBLIND = 104;
  const FONT_DOMINICAN = 105;
  const PLAYER_BOARDS = 107;
  const HAND_CARDS = 108;
  const PLAYER_RESOURCES = 109;
  const CARD_RULINGS_ICON = 110;
  const HIGHLIGHT_SHARED_SCORING = 111;
  const ACTION_CARD_DISPLAY = 150;

  return declare(
    'bgagame.agricola',
    [
      customgame.game,
      agricola.actionCards,
      agricola.players,
      agricola.playerBoard,
      agricola.meeples,
      agricola.sow,
      agricola.cards,
      agricola.reorganize,
      agricola.exchange,
      agricola.specialEffect,
      agricola.gameFlow,
      agricola.campaign,
    ],
    {
      constructor() {
        this._activeStates = [
          'construct',
          'exchange',
          'fencing',
          'improvement',
          'occupation',
          'payResources',
          'placeFarmer',
          'plow',
          'reorganize',
          'sow',
          'stables',
          'resolveChoice',
          'confirmTurn',
          'confirmPartialTurn',
          'renovation',
          'soloActionCardMode',
          'chooseActionCard',
        ];
        this._notifications = [
          ['revealActionCard', 1100],
          ['placeFarmer', null],
          ['addFences', null],
          ['addStables', null],
          ['collectResources', null],
          ['collectRoundResources', null],
          ['gainResources', null],
          ['payResources', null],
          ['accumulation', null],
          ['growFamily', null],
          ['growChildren', 1000],
          ['firstPlayer', 800],
          ['plow', 1000],
          ['sow', null],
          ['construct', 1000],
          ['renovate', 1000],
          ['returnHome', null],
          ['updateDropZones', 1],
          ['reorganize', null],
          ['harvestCrop', null],
          ['exchange', null],
          ['placeMeeplesForFuture', null],
          ['placeMeeplesFromSupply', null],
          ['buyCard', null],
          ['buyAndPassCard', null],
          ['buyAndDestroyCard', null],
          ['payWithCard', 1000],
          ['updateScores', 1],
          ['silentKill', null],
          ['silentDestroy', 1],
          ['clearTurn', 1],
          ['refreshUI', 1],
          ['refreshHand', 1],
          ['populateCardCache', 1],
          ['startHarvest', 3200],
          ['addCardToDraftSelection', 600],
          ['removeCardFromDraftSelection', 600],
          ['confirmDraftSelection', 1],
          ['clearDraftPools', 1000],
          ['newDraftPile', 800],
          ['draftProgress', 1],
          ['draftWaiting', 1],
          ['draftIsOver', 500],
          ['startNewTurn', 1],
          ['startWork', 1],
          ['startReturnHome', 1],
          ['startHarvestField', 1],
          ['startHarvestFeed', 1],
          ['startHarvestBreed', 1],
          ['seed', 10],
          ['updateHarvestCosts', 1],
          ['updateCardStats', 1],
          ['placeInfobox', 1],
          ['updateInfobox', 1],
          ['markUsableExchange', 1],
          ['unmarkUsableExchange', 1],
          ['unmarkAllUsableExchanges', 1],
          ['farmHandStableReturned', 800],
          ['farmHandStable', 800],
          ['updatePersonalOwned', 0],
          ['livingHandOfferCreated', 800],
          ['livingHandOfferCleared', 200],
          ['livingHandPicked', 800],
          ['livingHandPassDecision', 1100],
          ['debugCardToHand', 0],
          ['debugCardInPlay', 0],
          ['campaignPermanentChosen', 1],
          ['campaignNewGame', 1],
          ['campaignComplete', 1],
        ];

        this._canReorganize = false;
        this._isDraft = false;
        this._cardStorage = {};

        // Fix mobile viewport (remove CSS zoom)
        this.default_viewport = 'width=1000';

        this._cardScale = this.getConfig('agricolaCardScale', 80);
        this._cardAnimationSpeed = this.sanitizeCardAnimationSpeed(this.getConfig('agricolaAnimationSpeed', 80));
        this._centralBoardScale = this.getConfig('agricolaCentralBoardScale', 100);
        this._playerBoardScale = this.getConfig('agricolaPlayerBoardScale', 100);
      },

      /**
       * Setup:
       *	This method set up the game user interface according to current game situation specified in parameters
       *	The method is called each time the game interface is displayed to a player, ie: when the game starts and when a player refreshes the game page (F5)
       *
       * Params :
       *	- mixed gamedatas : contains all datas retrieved by the getAllDatas PHP method.
       */
      setup(gamedatas) {
        debug('SETUP', gamedatas);
        // Create a new div for "anytime" buttons
        dojo.place(
          "<div id='anytimeActions' style='display:inline-block;float:right'></div>",
          $('generalactions'),
          'after',
        );

        // 3D ribbon
        dojo.place('<div id="page-title-left-ribbon" class="ribbon-effect"></div>', 'page-title');
        dojo.place('<div id="page-title-right-ribbon" class="ribbon-effect"></div>', 'page-title');

        // Add Harvest Icons
        [4, 7, 9, 11, 13, 14].forEach((turn) => {
          if (turn >= gamedatas.turn) {
            dojo.place(`<div id="harvest-${turn}" class="harvest-icon"></div>`, 'harvest-slot-' + turn);
            this.addCustomTooltip('harvest-' + turn, _('Harvest will take place at the end of that turn'));
          }
        });

        dojo.attr('game_play_area', 'data-additional', gamedatas.isAdditional ? 1 : 0);
        dojo.attr('game_play_area', 'data-turn', gamedatas.turn);
        this.setupInfoPanel();
        this.setupScoresModal();
        this.setupActionCards();
        this.setupPlayers();
        this.setupPlayerCards();
        this.setupMeeples();
        this.setupAnimalsDropZones();
        this.updatePrefs();
        this.setCardScale(this._cardScale);
        this.setCardAnimationSpeed(this._cardAnimationSpeed);
        this.setCentralBoardScale(this._centralBoardScale);
        this.setPlayerBoardScale(this._playerBoardScale);
        if (gamedatas.seed != false) this.showSeed(gamedatas.seed);
        this.setupGameFlow();
        this.setupCampaignPanel();
        this._setupSnakeOpeningHintObserver();
        this.inherited(arguments);
      },

      onLoadingComplete() {
        if (localStorage.getItem('agricolaTour') != 1) {
          if (!this.isReadOnly) this.showTour();
        } else {
          dojo.style('tour-slide-footer', 'display', 'none');
          $('neverShowMe').checked = true;
        }

        this.inherited(arguments);
      },

      onPreferenceChange(pref, value) {
        this.updatePrefs();
        if (pref == PLAYER_BOARDS || pref == HAND_CARDS) {
          this.onScreenWidthChange();
        }
        if (pref == PLAYER_RESOURCES) {
          this.updateResourceBarsPositions();
        }
        if (pref == CARD_RULINGS_ICON) {
          this.refreshCardRulingsIcons();
        }
        if (pref == HIGHLIGHT_SHARED_SCORING) {
          this.refreshSharedScoringHighlights();
        }
        if (pref == ACTION_CARD_DISPLAY) {
          this.moveActionCardsToHolder(value == 1);
        }
      },

      updatePrefs() {
        dojo.toggleClass('ebd-body', 'colorblind', this.prefs[COLORBLIND].value == 1);
        dojo.toggleClass('ebd-body', 'disable-dominican', this.prefs[FONT_DOMINICAN].value == 1);
        this.onScreenWidthChange();
      },

      onScreenWidthChange() {
        dojo.toggleClass('player-boards', 'player-boards-right', this.prefs[PLAYER_BOARDS].value == 1);
        if (this.prefs[PLAYER_BOARDS].value == 1) {
          let gamePlaySize = $('game_play_area').offsetWidth;
          let playerBoard = document.querySelector('.player-board-wrapper');
          if (playerBoard == null) return;
          let playerBoardSize = playerBoard.offsetWidth + playerBoard.offsetLeft;
          dojo.toggleClass('player-boards', 'player-boards-right', playerBoardSize < gamePlaySize);
        }

        let isRight = dojo.hasClass('player-boards', 'player-boards-right');

        this.syncPlayerBoardsHeight(isRight);

        // Re-place hand container to match actual layout mode
        if (this.updateHandContainer) {
          this.updateHandContainer();
        }

        // Shift player boards up into game-flow-panel space
        let gfPanel = $('game-flow-panel');
        let gfOffset = gfPanel ? gfPanel.offsetHeight + parseFloat(getComputedStyle(gfPanel).marginBottom || 0) : 0;
        $('player-boards').style.setProperty('--gfOffset', gfOffset + 'px');

        if (this.updateActionCardsHolderMinHeight) {
          this.updateActionCardsHolderMinHeight();
        }
      },

      // Right mode positions the hand-cards column absolutely, so #player-boards must reserve its
      // height; the column grows as cards render after setup, so observe it and re-sync (else the
      // boards stay too short and the UI below overlaps the hand cards until a refresh).
      syncPlayerBoardsHeight(isRight) {
        let col = $('player-boards-left-column');
        if (isRight == null) isRight = dojo.hasClass('player-boards', 'player-boards-right');
        if (this.prefs[HAND_CARDS].value != 0 && isRight && col) {
          dojo.style('player-boards', 'min-height', col.offsetHeight + 'px');
        } else {
          dojo.style('player-boards', 'min-height', '');
        }
        if (col && !this._playerBoardsHeightObserver && window.ResizeObserver) {
          this._playerBoardsHeightObserver = new ResizeObserver(() => this.syncPlayerBoardsHeight());
          this._playerBoardsHeightObserver.observe(col);
        }
      },

      notif_clearTurn(n) {
        debug('Notif: restarting turn', n);
        this.cancelLogs(n.args.notifIds);

        // Farm Hand stable is a DOM-only overlay, so remove it on restart/reset
        document.querySelectorAll('.farmhand-stable').forEach((node) => node.remove());

      },

      notif_refreshUI(n) {
        debug('Notif: refreshing UI', n);

        // Remove DOM-only overlays before rebuild
        document.querySelectorAll('.farmhand-stable').forEach((node) => node.remove());

        ['meeples', 'players', 'scores', 'playerCards'].forEach((value) => {
          this.gamedatas[value] = n.args.datas[value];
        });

        if (Array.isArray(this.gamedatas.players)) {
          const map = {};
          this.gamedatas.players.forEach((p) => {
            map[p.id] = p;
          });
          this.gamedatas.players = map;
        }

        this.setupMeeples();
        this.setupAnimalsDropZones();
        this.updatePlayerCards();
        this.updatePlayersCounters(false);
        this.updatePlayersScores();

        // Re-draw Farm Hand stable overlays from refreshed gamedatas
        Object.values(this.gamedatas.players).forEach((player) => {
          if (player.board && player.board.farmHandStable) {
            this.addFarmHandStable(player.id, player.board.farmHandStable, false);
          }
        });

        window.requestAnimationFrame(() => this.updatePlayersCounters(false));
      },

      notif_livingHandPassDecision(n) {
        debug('Notif: living hand pass decision', n);
      },

      notif_refreshHand(n) {
        debug('Notif: refreshing UI', n);
        this.gamedatas.players[n.args.player_id].hand = n.args.hand;
        this.updateHandCards();
      },

      notif_populateCardCache(n) {
        debug('Notif: populating card cache', n);
        n.args.cards.forEach((card) => this.loadSaveCard(card));
      },

      onUpdateActionButtons(stateName, args) {
        //        this.addPrimaryActionButton('test', 'test', () => this.testNotif());
        this.inherited(arguments);
      },

      testNotif() {},

      notif_startHarvest(n) {
        debug('Notif: starting harvest', n);
        this._gameFlowPhase = 'harvest';
        this._gameFlowSubphase = null;
        this.renderGameFlow();

        let elem = $('harvest-' + n.args.turn);
        dojo.empty('card-overlay');
        if (this.isFastMode()) {
          dojo.destroy(elem);
          return;
        }

        dojo.place(elem, 'card-overlay');

        // Start animation
        this.slide(elem, 'card-overlay', {
          from: 'harvest-slot-' + n.args.turn,
        });
        dojo.addClass('card-overlay', 'active');
        dojo.style(elem, 'transform', `scale(5)`);

        setTimeout(() => dojo.removeClass('card-overlay', 'active'), 1800);
        setTimeout(() => dojo.destroy(elem), 3000);

        return 3200; // Always wait for harvest animation regardless of speed setting
      },

      clearPossible() {
        this.clearSowRadios();
        this.disableAnimalControls();
        dojo.empty('anytimeActions');
        dojo.query('.player-board-wrapper.current.harvest').removeClass('harvest');
        dojo.query('.phantom').removeClass('.phantom');
        this._isHarvest = false;
        if (this._exchangeDialog) this._exchangeDialog.hide();
        if (this._showSeedDialog) this._showSeedDialog.destroy();
        if (this._campaignIntroModal) this._campaignIntroModal.hide();
        this._majorsDialog.hide();
        this.inherited(arguments);
      },

      onEnteringState(stateName, args) {
        debug('Entering state: ' + stateName, args);
        this._renderSnakeOpeningHint();
        if (this.isFastMode() && !['draftPlayers', 'livingHandRefill', 'livingHandPassingDecision', 'soloActionCardMode', 'chooseActionCard'].includes(stateName)) return;

        if (stateName == 'exchange' && args.args && args.args.automaticAction) {
          args.args.descSuffix = 'cook';
        }

        if (args.args && args.args.descSuffix) {
          this.changePageTitle(args.args.descSuffix);
        }

        if (args.args && args.args.optionalAction) {
          let base = args.args.descSuffix ? args.args.descSuffix : '';
          this.changePageTitle(base + 'skippable');
        }

        if (args.args && args.args.source) {
          if (this.gamedatas.gamestate.descriptionmyturn.search('{source}') === -1) {
            $('pagemaintitletext').appendChild(document.createTextNode(` (${_(args.args.source)})`));
          }
        }

        if (this._activeStates.includes(stateName) && !this.isCurrentPlayerActive()) return;

        if (args.args && args.args.optionalAction && !args.args.automaticAction) {
          this.addSecondaryActionButton('btnPassAction', _('Pass'), () => this.takeAction('actPassOptionalAction'));
        }

        // Undo last steps
        if (args.args && args.args.previousSteps) {
          args.args.previousSteps.forEach((stepId) => {
            let logEntry = $('logs').querySelector(`.log.notif_newUndoableStep[data-step="${stepId}"]`);
            if (logEntry) this.onClick(logEntry, () => this.undoToStep(stepId));

            logEntry = document.querySelector(`.chatwindowlogs_zone .log.notif_newUndoableStep[data-step="${stepId}"]`);
            if (logEntry) this.onClick(logEntry, () => this.undoToStep(stepId));
          });
        }

      // Restart turn button
      if (args.args && args.args.previousEngineChoices && args.args.previousEngineChoices >= 1 && !args.args.automaticAction) {
        if (args.args && args.args.previousSteps) {
          let lastStep = Math.max(...args.args.previousSteps);
          if (lastStep > 0)
            this.addDangerActionButton('btnUndoLastStep', _('Undo last step'), () => this.undoToStep(lastStep), 'restartAction');
        }

        // Restart whole turn
        this.addDangerActionButton(
          'btnRestartTurn',
          _('Restart turn'),
          () => {
            this.stopActionTimer();
            this.takeAction('actRestart');
          },
          'restartAction'
        );
      }

        if (this.isCurrentPlayerActive() && args.args) {
          // Anytime buttons
          if (args.args.anytimeActions) {
            args.args.anytimeActions.forEach((action, i) => {
              let msg = action.desc;
              msg = msg.log ? this.format_string_recursive(msg.log, msg.args) : _(msg);
              msg = this.formatStringMeeples(msg);

              this.addPrimaryActionButton(
                'btnAnytimeAction' + i,
                msg,
                () => this.takeAction('actAnytimeAction', { id: i }, false),
                'anytimeActions',
              );
            });
          }
        }

        // Call appropriate method
        var methodName = 'onEnteringState' + stateName.charAt(0).toUpperCase() + stateName.slice(1);
        if (this[methodName] !== undefined) this[methodName](args.args);
      },

      onAddingNewUndoableStepToLog(notif) {
        if (!$(`log_${notif.logId}`)) return;
        let stepId = notif.msg.args.stepId;
        $(`log_${notif.logId}`).dataset.step = stepId;
        if ($(`dockedlog_${notif.mobileLogId}`)) $(`dockedlog_${notif.mobileLogId}`).dataset.step = stepId;

        if (
          this.gamedatas &&
          this.gamedatas.gamestate &&
          this.gamedatas.gamestate.args &&
          this.gamedatas.gamestate.args.previousSteps &&
          this.gamedatas.gamestate.args.previousSteps.includes(parseInt(stepId))
        ) {
          this.onClick($(`log_${notif.logId}`), () => this.undoToStep(stepId));

          if ($(`dockedlog_${notif.mobileLogId}`)) this.onClick($(`dockedlog_${notif.mobileLogId}`), () => this.undoToStep(stepId));
        }
      },

      undoToStep(stepId) {
        this.stopActionTimer();
        this.checkAction('actRestart');
        this.takeAction('actUndoToStep', { stepId }, false);
      },

      addActionChoiceBtn(choice, disabled = false) {
        if ($('btnChoice' + choice.id)) return;

        let desc =
          typeof choice.description == 'string'
            ? _(choice.description)
            : this.format_string_recursive(_(choice.description.log), choice.description.args);
        desc = this.formatStringMeeples(desc);

        if (desc == "") {
          return;
        }

        this.addSecondaryActionButton(
          'btnChoice' + choice.id,
          desc,
          disabled ? () => {} : () => this.takeAction('actChooseAction', { id: choice.id }),
        );
        if (disabled) {
          dojo.addClass('btnChoice' + choice.id, 'disabled');
        }
      },

      onEnteringStateResolveChoice(args) {
        Object.values(args.choices).forEach((choice) => this.addActionChoiceBtn(choice, false));
        Object.values(args.allChoices).forEach((choice) => this.addActionChoiceBtn(choice, true));
      },

      onEnteringStateImpossibleAction(args) {
        this.addActionChoiceBtn(
          {
            choiceId: 0,
            description: args.desc,
          },
          true,
        );

        // Escape hatch: only when a full restart can't undo back to turn start.
        // The mandatory action is still illegal; this only exists because
        // the engine has no other way to release the player and the game would otherwise be completely stuck
        if (args.canAbandon && this.isCurrentPlayerActive()) {
          this.addDangerActionButton(
            'btnAbandonStuckAction',
            _('Abandon action (cannot restart turn)'),
            () => {
              this.confirmationDialog(
                _(
                  'You are abandoning a mandatory action. This is normally illegal, but you cannot restart your turn because other players have already committed effects. Continue?'
                ),
                () => {
                  this.takeAction('actAbandonStuckAction');
                }
              );
            },
            'restartAction'
          );
        }
      },

      addConfirmTurn(args, action) {
        this.addPrimaryActionButton('btnConfirmTurn', _('Confirm'), () => {
          this.stopActionTimer();
          this.takeAction(action);
        });

        const OPTION_CONFIRM = 103;
        let n = args.previousEngineChoices;
        let timer = Math.min(10 + 2 * n, 20);
        this.startActionTimer('btnConfirmTurn', timer, this.prefs[OPTION_CONFIRM].value);
      },

      onEnteringStateConfirmTurn(args) {
        this.addConfirmTurn(args, 'actConfirmTurn');
      },

      onEnteringStateConfirmPartialTurn(args) {
        this.addConfirmTurn(args, 'actConfirmPartialTurn');
      },

      onEnteringStateSoloActionCardMode(args) {
        this.addPrimaryActionButton('btnActionCardsRandom', _('Reveal in random order'), () =>
          this.takeAction('actSoloActionCardMode', { mode: 'random' }),
        );
        this.addPrimaryActionButton('btnActionCardsChoose', _('I choose each round'), () =>
          this.takeAction('actSoloActionCardMode', { mode: 'choose' }),
        );
      },

      onEnteringStateChooseActionCard(args) {
        args.cards.forEach((card) => {
          this.addPrimaryActionButton('btnChoose' + card.id, _(card.name), () =>
            this.takeAction('actChooseActionCard', { cardId: card.id }),
          );
        });
      },

      /************************
       **** ATOMIC ACTIONS *****
       ************************/
      onEnteringStatePlaceFarmer(args) {
        this.promptActionCard(args.cards, (cId) => this.takeAtomicAction('actPlaceFarmer', [cId]), args.allCards);
      },

      onEnteringStateFencing(args) {
        const setFencingStatus = (html) => {
          if ($('gameaction_status')) {
            $('gameaction_status').innerHTML = html;
          }
          if ($('pagemaintitletext')) {
            $('pagemaintitletext').innerHTML = html;
          }
        };

        const setStep1Status = () => {
          setFencingStatus(this.formatStringMeeples(_('Choose how many Wood Palisades to place (2 <WOOD> each)')));
        };

        const setStep2Status = (max) => {
          setFencingStatus(dojo.string.substitute(_('You may construct up to ${n} fence(s)'), { n: max }));
        };

        const submitFencing = (zones, woodPalisadesCount = 0) => {
          if (args.midnightFencer) {
            const caps = args.donorCaps || {};
            const need = zones.length;

            let totalCaps = 0;
            const donorsWithCap = [];

            Object.keys(caps).forEach((pid) => {
              const cap = caps[pid] || 0;
              if (cap > 0) {
                donorsWithCap.push(pid);
                totalCaps += cap;
              }
            });

            // Case 1: no choice – must take all available fences
            if (need === totalCaps) {
              const donors = {};
              donorsWithCap.forEach((pid) => {
                donors[pid] = caps[pid];
              });

              this.takeAtomicAction('actFence', [zones, donors]);
              return;
            }

            // Case 2: no choice – only one possible donor
            if (donorsWithCap.length === 1) {
              const pid = donorsWithCap[0];
              const donors = {};
              donors[pid] = Math.min(caps[pid], need);

              this.takeAtomicAction('actFence', [zones, donors]);
              return;
            }

            // Otherwise, there is a real choice
            this.E149_MidnightFencer(args, zones);
            return;
          }

          if (!args.woodPalisades || woodPalisadesCount <= 0) {
            this.takeAtomicAction('actFence', [zones]);
            return;
          }

          this.takeAtomicAction('actFence', [zones, [], woodPalisadesCount]);
        };

        const startFenceSelection = (woodPalisadesCount = 0) => {
          dojo.empty('customActions');
          if (args.optionalAction) {
            this.addSecondaryActionButton('btnPassAction', _('Pass'), () => this.takeAction('actPassOptionalAction'));
          }

          const maxSelectable = this.B30_getMaxFencesForCount(args, woodPalisadesCount);
          if (args.woodPalisades) {
            setStep2Status(maxSelectable);
          }
          const callbackCheck =
            woodPalisadesCount > 0
              ? (zones) => this.B30_countBorderFenceZones(zones) >= woodPalisadesCount
              : null;

          this.promptPlayerBoardZones(
            args.zones,
            1,
            maxSelectable,
            (zones) => submitFencing(zones, woodPalisadesCount),
            callbackCheck
          );
        };

        if (args.midnightFencer || !args.woodPalisades) {
          startFenceSelection(0);
          return;
        }

        setStep1Status();
        this.B30_promptWoodPalisadesCount(args, (count) => startFenceSelection(count));
      },

      B30_isBorderFenceZone(zone) {
        return zone.x == 0 || zone.x == 10 || zone.y == 0 || zone.y == 6;
      },

      B30_countBorderFenceZones(zones) {
        return zones.filter((z) => this.B30_isBorderFenceZone(z)).length;
      },

      B30_getMaxFencesForCount(args, count) {
        const options = args.woodPalisadesOptions || {};
        const key = String(count);
        if (Object.prototype.hasOwnProperty.call(options, key)) {
          const parsed = parseInt(options[key], 10);
          if (!Number.isNaN(parsed)) {
            return parsed;
          }
        }
        return args.max;
      },

      B30_promptWoodPalisadesCount(args, callback) {
        dojo.empty('customActions');
        if (args.optionalAction) {
          this.addSecondaryActionButton('btnPassAction', _('Pass'), () => this.takeAction('actPassOptionalAction'));
        }

        let options = Object.keys(args.woodPalisadesOptions || {})
          .map((countKey) => {
            const count = parseInt(countKey, 10);
            const max = parseInt(args.woodPalisadesOptions[countKey], 10);
            return { count, max };
          })
          .filter((opt) => !Number.isNaN(opt.count) && !Number.isNaN(opt.max) && opt.max >= 1 && opt.max >= opt.count)
          .sort((a, b) => a.count - b.count);

        if (options.length == 0) {
          options = [{ count: 0, max: args.max }];
        }

        options.forEach((opt) => {
          this.addPrimaryActionButton(`B30_palisades_${opt.count}`, `${opt.count}`, () => callback(opt.count));
        });
      },
      onEnteringStatePlow(args) {
        this.promptPlayerBoardZones(args.zones, 1, null, (zone) => this.takeAtomicAction('actPlow', [zone]));
      },

      onEnteringStateConstruct(args) {
        this.promptPlayerBoardZones(args.zones, 1, args.max, (zones) => this.takeAtomicAction('actConstruct', [zones]));
      },

      onEnteringStateStables(args) {
        if (args.farmHandStable) {
          this.addFarmHandStable(this.player_id, args.farmHandStable, false);
        }

        if (args.automaticAction) {
          return;
        }

        // Interactive selection. Server-side min/max still enforce legality.
        this.promptPlayerBoardZones(
          args.zones,
          args.min,
          args.max,
          (zones) => this.takeAtomicAction('actStables', [zones]),
          null,
          { showConfirmWhenEmpty: true }
        );
      },

      onEnteringStateImprovement(args) {
        if (args._private.cards.length != 0) {
          this.promptCard(args.types, args._private.cards, (cardId) => {
            let warning = ['A3_PaperKnife', 'B3_Moonshine'].includes(cardId);
            this.askConfirmation(warning, () => this.takeAtomicAction('actImprovement', [cardId]));
          });
        }
      },

      askConfirmation(warning, callback) {
        if (warning === false) {
          callback();
        } else {
          let msg =
            warning === true
              ? _(
                  "If you take this action, you won't be able to undo past this step because you will reveal information from a random process"
                )
              : warning;
          this.confirmationDialog(
            msg,
            () => {
              callback();
            }
          );
        }
      },

      onEnteringStateOccupation(args) {
        this.promptCard(args.types, args._private.cards, (cardId) => this.takeAtomicAction('actOccupation', [cardId]));
      },

      onEnteringStatePayResources(args) {
        if (args.combinations.length == 1) {
          return;
        }

        args.combinations.forEach((cost, i) => {
          // Compute desc
          let log = '',
            arg = {};
          if (cost.card == undefined) {
            if (cost.sources && cost.sources.length) {
              log = _('Pay ${resource} (${cards})');
              arg.resource = this.formatResourceArray(cost);
              arg.cards = cost.sources.map((cardId) => _(args.cardNames[cardId])).join(', ');
            } else {
              log = _('Pay ${resource}');
              arg.resource = this.formatResourceArray(cost);
            }
          } else {
            log = _('Return ${card}');

            // Make fireplaces distinguishable
            let extraCardInfo = '';
            switch (cost.card) {
              case 'A60_OrientalFireplace':
                log = _('Remove ${card} from the game');
                break;
              case 'Major_Fireplace1':
                extraCardInfo = ' (2' + this.formatStringMeeples('<CLAY>') + ')';
                break;
              case 'Major_Fireplace2':
                extraCardInfo = ' (3' + this.formatStringMeeples('<CLAY>') + ')';
                break;
              case 'Major_CookingHearth1':
                extraCardInfo = ' (4' + this.formatStringMeeples('<CLAY>') + ')';
                break;
              case 'Major_CookingHearth2':
                extraCardInfo = ' (5' + this.formatStringMeeples('<CLAY>') + ')';
                break;
            }
            arg.card = _(args.cardNames[cost.card] + extraCardInfo);
          }
          let desc = this.format_string_recursive(log, arg);

          // Add button
          this.addSecondaryActionButton('btnChoicePay' + i, desc, () => this.takeAtomicAction('actPay', [cost]));
        });
      },

      onEnteringStateRenovation(args) {
        if (args.combinations.length == 1) {
          return;
        }

        args.combinations.forEach((cost, i) => {
          // Compute desc
          let log = '',
            arg = {};
          if (cost == 'roomClay') {
            log = _('Renovate to clay');
          } else if (cost == 'roomStone') {
            log = _('Renovate to stone');
          }

          // Add button
          this.addSecondaryActionButton('btnChoiceRenovate' + i, log, () =>
            this.takeAtomicAction('actRenovation', [cost]),
          );
        });
      },

      // Generic call for Atomic Action that encode args as a JSON to be decoded by backend
      takeAtomicAction(action, args) {
        if (!this.checkAction(action)) return false;

        this.takeAction('actTakeAtomicAction', { actionArgs: JSON.stringify(args) }, false);
      },

      /************************
       ******* SETTINGS ********
       ************************/
      setupInfoPanel() {
        dojo.place(
          this.format_string(jstpl_configPlayerBoard, {
            sectionLayout: _('LAYOUT'),
            sectionGameFlow: _('GAME FLOW'),
            sectionAccessibility: _('APPEARANCE & ACCESSIBILITY'),
            centralBoardSize: _('Size of central board(s)'),
            playerBoardSize: _('Size of player board(s)'),
            cardSize: _('Size of cards'),
            playCard: _('Animation speed'),
          }),
          'player_boards',
          'first',
        );
        dojo.connect($('show-settings'), 'onclick', () => this.toggleSettings());
        this.addTooltip('show-settings', '', _('Display settings about the game.'));

        let chk = $('help-mode-chk');
        dojo.connect(chk, 'onchange', () => this.toggleHelpMode(chk.checked));
        this.addTooltip('help-mode-switch', '', _('Toggle help/safe mode.'));
        this.setupSettings();
        this.setupHelper();
        this.setupTour();
      },

      updatePlayerOrdering() {
        this.inherited(arguments);
        dojo.place('player_board_config', 'player_boards', 'first');
      },

      toggleSettings() {
        dojo.toggleClass('settings-controls-container', 'settingsControlsHidden');

        // Hacking BGA framework
        if (dojo.hasClass('ebd-body', 'mobile_version')) {
          dojo.query('.player-board').forEach((elt) => {
            if (elt.style.height != 'auto') {
              dojo.style(elt, 'min-height', elt.style.height);
              elt.style.height = 'auto';
            }
          });
        }
      },

      setupBooleanPref(prefId, sectionId, checkedValue) {
        var select = $('preference_control_' + prefId);
        if (!select) return;
        if (checkedValue === undefined) checkedValue = 1;
        var uncheckedValue = checkedValue == 1 ? 0 : 1;

        var originalRow = select.parentNode.parentNode;

        // Extract translated label from BGA-generated DOM
        var clone = originalRow.cloneNode(true);
        var clonedSelect = clone.querySelector('select');
        if (clonedSelect) clonedSelect.remove();
        var label = clone.textContent.trim();

        // Hide original row but keep it in DOM for BGA preference sync
        originalRow.style.display = 'none';
        dojo.place(originalRow, sectionId);

        // Create compact checkbox row
        var row = dojo.create('div', { class: 'row-data row-data-boolean' }, sectionId);
        row.innerHTML =
          '<label class="boolean-setting">' +
          '<input type="checkbox" class="boolean-setting-chk"' +
          (select.value == checkedValue ? ' checked' : '') +
          ' />' +
          '<span class="boolean-setting-mark"></span>' +
          '<span class="boolean-setting-text">' +
          label +
          '</span>' +
          '</label>';

        row.querySelector('.boolean-setting-chk').addEventListener('change', function () {
          select.value = this.checked ? checkedValue : uncheckedValue;
          select.dispatchEvent(new Event('change', { bubbles: true }));
        });
      },

      setupSettings() {
        // LAYOUT section
        dojo.place($('preference_control_107').parentNode.parentNode, 'settings-section-layout');
        dojo.place($('preference_control_108').parentNode.parentNode, 'settings-section-layout');
        this.setupBooleanPref(109, 'settings-section-layout');
        this.setupBooleanPref(150, 'settings-section-layout');

        // GAME FLOW section
        dojo.place($('preference_control_103').parentNode.parentNode, 'settings-section-gameflow');
        dojo.place($('preference_control_102').parentNode.parentNode, 'settings-section-gameflow');
        dojo.place($('preference_control_106').parentNode.parentNode, 'settings-section-gameflow');

        // ACCESSIBILITY / APPEARANCE section
        this.setupBooleanPref(110, 'settings-section-accessibility');
        this.setupBooleanPref(111, 'settings-section-accessibility');
        this.setupBooleanPref(104, 'settings-section-accessibility');
        dojo.place($('preference_control_105').parentNode.parentNode, 'settings-section-accessibility');

        this._cardScaleSlider = document.getElementById('layout-control-card-size');
        noUiSlider.create(this._cardScaleSlider, {
          start: [this._cardScale],
          step: 5,
          padding: 10,
          range: {
            min: [40],
            max: [120],
          },
        });
        this._cardScaleSlider.noUiSlider.on('slide', (arg) => this.setCardScale(parseInt(arg[0])));

        this._cardAnimationSpeedSlider = document.getElementById('layout-control-card-animation-speed');
        noUiSlider.create(this._cardAnimationSpeedSlider, {
          start: [this._cardAnimationSpeed],
          step: 5,
          padding: 10,
          range: {
            min: [30],
            max: [200],
          },
        });
        this._cardAnimationSpeedSlider.noUiSlider.on('slide', (arg) => this.setCardAnimationSpeed(parseInt(arg[0])));

        this._centralBoardScaleSlider = document.getElementById('layout-control-central-board-size');
        noUiSlider.create(this._centralBoardScaleSlider, {
          start: [this._centralBoardScale],
          step: 5,
          padding: 10,
          range: {
            min: [60],
            max: [140],
          },
        });
        this._centralBoardScaleSlider.noUiSlider.on('slide', (arg) => this.setCentralBoardScale(parseInt(arg[0])));

        this._playerBoardScaleSlider = document.getElementById('layout-control-player-board-size');
        noUiSlider.create(this._playerBoardScaleSlider, {
          start: [this._playerBoardScale],
          step: 5,
          padding: 10,
          range: {
            min: [40],
            max: [140],
          },
        });
        this._playerBoardScaleSlider.noUiSlider.on('slide', (arg) => this.setPlayerBoardScale(parseInt(arg[0])));
      },

      setCardScale(scale) {
        this._cardScale = scale;

        let applyScale = (elt, scale) => {
          elt.style.setProperty('--agricolaCardWidth', (235 * scale) / 100 + 'px');
          elt.style.setProperty('--agricolaCardHeight', (374 * scale) / 100 + 'px');
          elt.style.setProperty('--agricolaCardScale', scale / 100);
        };

        applyScale(document.documentElement, scale);
        document.querySelectorAll('.player-board-wrapper').forEach((board) => applyScale(board, 0.8 * scale));

        localStorage.setItem('agricolaCardScale', scale);
      },

      sanitizeCardAnimationSpeed(speed) {
        const DEFAULT_SPEED = 80;
        const MIN_SPEED = 30;
        const MAX_SPEED = 200;
        const parsed = parseInt(speed, 10);

        if (Number.isNaN(parsed)) {
          return DEFAULT_SPEED;
        }
        return Math.max(MIN_SPEED, Math.min(MAX_SPEED, parsed));
      },

      setCardAnimationSpeed(speed) {
        this._cardAnimationSpeed = this.sanitizeCardAnimationSpeed(speed);
        localStorage.setItem('agricolaAnimationSpeed', this._cardAnimationSpeed);
        document.body.classList.toggle('instant-speed', this.isInstantSpeed());
      },

      getAnimationSpeedMultiplier() {
        let speed = this._cardAnimationSpeed || 80;
        if (this.isInstantSpeed()) return 0;
        // speed 15 (slowest) => multiplier ~5.3, speed 80 (default) => 1.0, speed 200 (fastest) => 0.4
        return 80 / speed;
      },

      isInstantSpeed() {
        return (this._cardAnimationSpeed || 80) >= 190;
      },

      setCentralBoardScale(scale) {
        this._centralBoardScale = scale;
        document.documentElement.style.setProperty('--agricolaCentralBoardScale', scale / 100);
        localStorage.setItem('agricolaCentralBoardScale', scale);
        this.onScreenWidthChange();
      },

      setPlayerBoardScale(scale) {
        this._playerBoardScale = scale;
        document.documentElement.style.setProperty('--agricolaPlayerBoardScale', scale / 100);
        localStorage.setItem('agricolaPlayerBoardScale', scale);
        this.updatePlayerBoardDimensions();
      },

      updatePlayerBoardDimensions(pId = null) {
        let ids = pId == null ? Object.keys(this.gamedatas.players) : [pId];
        ids.forEach((pId) => {
          let holder = $('board-wrapper-' + pId);
          dojo.style('player-board-resizable-' + pId, {
            width: (holder.offsetWidth * this._playerBoardScale) / 100 + 'px',
            height: (holder.offsetHeight * this._playerBoardScale) / 100 + 'px',
          });
        });
      },

      /*
       * Display a helper with global scoring
       */
      setupHelper() {
        this._helperModal = new customgame.modal('showHelpsheet', {
          class: 'agricola_popin',
          closeIcon: 'fa-times',
          openAnimation: true,
          openAnimationTarget: 'show-help',
          title: _('Help sheet'),
          closeAction: 'hide',
          verticalAlign: 'flex-start',
        });

        dojo.connect($('show-help'), 'onclick', () => this.showHelper());
        this.addTooltip('show-help', '', _('Show scoring helpsheet.'));
      },

      showHelper() {
        this._helperModal.show();
      },

      /********************
       ***** LOAD SEED *****
       ********************/
      onEnteringStateLoadSeed() {
        // Create modal
        this._showSeedDialog = new customgame.modal('showSeedPrompt', {
          autoShow: true,
          class: 'agricola_popin',
          closeIcon: 'fa-times',
          title: _('Please enter a seed'),
          closeAction: 'hide',
          contents: `
            <div id="seed-form-container">
              <textarea id="seed-form-input"></textarea>
            </div>
            <div id="seed-dialog-footer"></div>
          `,
        });

        this.addPrimaryActionButton('btnShowSeedPrompt', _('Show form'), () => dialog.show());
        this.addPrimaryActionButton(
          'btnConfirmSeedPrompt',
          _('Confirm'),
          () => this.onConfirmSeed(),
          'seed-dialog-footer',
        );
      },

      onConfirmSeed() {
        let seed = $('seed-form-input').value;
        this.takeAction('actLoadSeed', { seed: JSON.stringify(seed) });
      },

      notif_seed(n) {
        debug('Notif: receiving seed', n);
        this.showSeed(n.args.seed);
      },

      showSeed(seed) {
        if ($('game-seed')) return;
        let label = _('Want to play with the same configuration? Here is the seed of your game:');
        let copyLabel = _('Copy');
        dojo.place(
          `<div id="game-seed">
             <span id="game-seed-label">${label}</span>
             <input id="game-seed-input" type="text" readonly>
             <button id="game-seed-copy" type="button">${copyLabel}</button>
           </div>`,
          'anytimeActions',
          'after',
        );
        $('game-seed-input').value = seed;
        dojo.connect($('game-seed-copy'), 'click', () => this.copySeedToClipboard(seed));
      },

      copySeedToClipboard(seed) {
        let input = $('game-seed-input');
        if (input) {
          input.focus();
          input.select();
          input.setSelectionRange(0, seed.length);
        }
        let btn = $('game-seed-copy');
        let flash = (msg) => {
          if (!btn) return;
          btn.textContent = msg;
          setTimeout(() => {
            if ($('game-seed-copy')) $('game-seed-copy').textContent = _('Copy');
          }, 1500);
        };
        if (navigator.clipboard && navigator.clipboard.writeText) {
          navigator.clipboard.writeText(seed).then(
            () => flash(_('Copied!')),
            () => flash(_('Copy failed')),
          );
        } else {
          try {
            flash(document.execCommand('copy') ? _('Copied!') : _('Copy failed'));
          } catch (e) {
            flash(_('Copy failed'));
          }
        }
      },

      /********************
       ***** TOUR *****
       ********************/

      /*
       * Display an helper tour
       */
      setupTour() {
        this._tourModal = new customgame.modal('showTour', {
          class: 'agricola_popin',
          closeIcon: 'fa-times',
          openAnimation: true,
          openAnimationTarget: 'uwe-help',
          title: _('Agricola Tour'),
          contents: this.tplTourContent(),
          closeAction: 'hide',
          verticalAlign: 'flex-start',
        });

        dojo.connect($('uwe-help'), 'onclick', () => this.showTour());
        this.addTooltip('uwe-help', '', _('Show help tour.'));

        dojo.query('#tour-slider-container .tour-link').forEach((elt) => {
          let href = elt.getAttribute('href');
          dojo.connect(elt, 'click', () => this.setTourSlide(href));
        });

        dojo.connect($('neverShowMe'), 'change', function () {
          localStorage.setItem('agricolaTour', this.checked ? 1 : 0);
        });
      },

      showTour() {
        this._tourModal.show();
        this.setTourSlide('intro');
      },

      setTourSlide(link) {
        dojo.query('#tour-slider-container .slide').addClass('inactive');
        dojo.removeClass('tour-slide-' + link, 'inactive');
      },

      tplTourContent() {
        let nextBtn = (link, text = null) =>
          `<div class='tour-btn'><button href="${link}" class="action-button bgabutton bgabutton_blue tour-link">${
            text == null ? _('Next') : text
          }</button></div>`;

        let introBubble = _(
          "Welcome to Agricola on BGA. I'm here to give you a tour of the interface, to make sure you'll enjoy your games to the fullest.",
        );
        let introSectionUI = _('Global interface overview');
        let introSectionScoring = _('Scoring');
        let introSectionCards = _('Cards FAQ');
        let introSectionBugs = _('Report a bug');

        let panelInfoBubble = _("Let's start with this panel next to your name: a very handy toolbox.");
        let panelInfoItems = [
          _('my face: open this tour if you need it later'),
          _(
            "the switch toggles safe/help mode: when enabled, clicking will do nothing, and instead will open tooltips on any element with a question mark on it, making sure you won't misclick",
          ),
          _('the star calendar: details of live scores (only if corresponding game option was enabled)'),
          _('the star: a breakdown of the endgame scoring'),
          _(
            "settings: this implementation comes with a lot of ways to customize your experience to your needs. Take some time to play with them until you're comfortable",
          ),
        ];

        let centralBoardBubble = _(
          'This is the central board where you take your actions. It consists of these parts:',
        );
        let centralBoardItems = [
          _(
            'Primary action spaces: available at the start of the game, the left column may vary or disappear based on the number of players',
          ),
          _(
            'Action space cards: one card is revealed at the start of each round. They are organized in 6 stages, randomized within each stage (the question mark on the round numbers preview the possible action space cards.) Each stage ends with a harvest:',
          ),
          _(
            'Additional tile: this is only present if you enable the corresponding game option. It provides additional actions that are linked together: if a player places a farmer on one of the actions, all the other linked actions also become unavailable.',
          ),
          _(
            'You may notice this section is adapted to be tablet/mobile-friendly (less wide than real components). It is publisher-approved and affected cards will be adapted or omitted.',
          ),
        ];

        let cardsBtnBubble = _('Where are all the cards?');
        let cardsBtnItems = [
          _('Clicking the Fireplace icon brings up the Major Improvements board.'),
          _(
            'The other icon (with mixed card types) brings up your hand of Occupations and Minor Improvements. It will not appear if you are in Beginner Mode, or if you have customized the setting to move it elsewhere.',
          ),
        ];

        let playerBoardBubble = _(
          'This is your personal farmyard board. Here you can build rooms, build fences, build stables, or plow fields.',
        );
        let playerPanelBubble = _('These player panels contain a lot of useful information!');
        let playerPanelItems = [
          _('Top section: resources and food in personal supply.'),
          _('Middle section: animals currently on farm, and actions remaining.'),
          _(
            'Bottom section: a reminder that each player can only have up to 5 farmers, 15 fence pieces, and 4 stables!',
          ),
          _(
            'The number after the / in your food reserve is a reminder of how much food the player currently needs for the next harvest.',
          ),
        ];

        let cookBubble = _(
          'In Agricola, cooking animals and crops can be a crucial step to provide the food you need to feed your people.',
        );
        let cookItems = [
          _(
            'All resource conversions happen in this window: just click on the exchanges you want to make before hitting "Confirm".',
          ),
          _(
            'If you need to make an exchange, just click on this button to open the previous modal (button is only present if you can do at least one exchange).',
          ),
        ];

        let reorganizeBubble = _(
          'Whenever you obtain animals, you must immediately accommodate them in your farm, otherwise they will be discarded (or cooked if you have the proper improvement.)',
        );
        let reorganizeItems = [
          _(
            'Most of the time, the game will automatically accommodate your animals in the best spot ever. However, you may also arrange them manually by either changing the "automatic" setting or by clicking this button:',
          ),
          _(
            'Click on the small arrows that pop up in your pastures, stables, and rooms to choose how your animals should be arranged.',
          ),
          _(
            'These controls will clear all, decrease, increase, or maximize a specific animal type in each of your pastures/stables/rooms.',
          ),
        ];

        let bugBubble = _('No code is error-free. Before reporting a bug, please follow these steps.');
        let bugItems = [
          _(
            'If the issue is related to a card, please use the unique identifier (deck + number, e.g. A001) instead of the name.',
          ),
          _(
            'If your language is not English, please check the English card description using the unique identifier. If there is an incorrect translation to your language, please do not report a bug and use the translation module (Community > Translation) to fix it directly.',
          ),
          _(
            'For rules-related bugs, please double-check the rulebook before reporting to make sure you have it correct. The majority of rules-related bugs submitted so far... have not actually been bugs :/',
          ),
          _(
            'When you encounter a bug, please refresh your page to see if the bug goes away. Knowing whether or not a bug is persisting through refresh or not will help us find and fix it, so please include that in the bug report!',
          ),
        ];
        let bugReport = _('Report a new bug');

        let neverShowMe = _('Never show me this tour again');

        var bugUrl = this.metasiteurl + '/bug?id=0&table=' + this.table_id;

        return `
            <div id="tour-slider-container">
              <div id="tour-slide-intro" class="slide">
                <div class="bubble">${introBubble}</div>
                  <button href="panelInfo" class="action-button bgabutton bgabutton_blue tour-link">${introSectionUI}</button>
                  <button href="bugs" class="action-button bgabutton bgabutton_red tour-link">${introSectionBugs}</button>
                </ul>
              </div>

              <div id="tour-slide-panelInfo" class="slide">
                <div class="bubble">${panelInfoBubble}</div>
                <div class="split-hor">
                  <div>
                    <div id="img-panelInfo" class="tour-img"></div>
                  </div>
                  <div>
                    <ul>
                      <li>${panelInfoItems[0]}</li>
                      <li>${panelInfoItems[1]}</li>
                      <li>${panelInfoItems[2]}</li>
                      <li>${panelInfoItems[3]}</li>
                      <li>${panelInfoItems[4]}</li>
                    </ul>
                  </div>
                </div>
                ${nextBtn('centralBoard')}
              </div>

              <div id="tour-slide-centralBoard" class="slide">
                <div class="bubble">${centralBoardBubble}</div>
                <div class="split-hor">
                  <div>
                    <div class="tour-img" id="img-centralBoard"></div>
                  </div>
                  <div>
                    <ul>
                      <li>${centralBoardItems[0]}</li>
                      <li>
                        ${centralBoardItems[1]}
                        <div class="tour-img" id="img-harvest"></div>
                      </li>
                      <li>${centralBoardItems[2]}</li>
                    </ul>
                  </div>
                </div>
                <div class="tour-remark">${centralBoardItems[3]}</div>
                ${nextBtn('cardsBtn')}
              </div>

              <div id="tour-slide-cardsBtn" class="slide">
                <div class="bubble">${cardsBtnBubble}</div>
                <div class="split-hor">
                  <div>
                    <ul>
                      <li>${cardsBtnItems[0]}</li>
                      <li>${cardsBtnItems[1]}</li>
                    </ul>
                  </div>
                  <div>
                    <div class="tour-img" id="img-cardsBtn"></div>
                  </div>
                </div>
                ${nextBtn('boardPanel')}
              </div>


              <div id="tour-slide-boardPanel" class="slide">
                <div class="bubble">${playerBoardBubble}</div>
                <div class="tour-img" id="img-player-board"></div>

                <div class="split-hor">
                  <div>
                    <div class="tour-img" id="img-player-panel"></div>
                  </div>
                  <div>
                    <div class="bubble">${playerPanelBubble}</div>
                    <ul>
                      <li>${playerPanelItems[0]}</li>
                      <li>${playerPanelItems[1]}</li>
                      <li>${playerPanelItems[2]}</li>
                    </ul>
                  </div>
                </div>
                <div class="tour-remark">${playerPanelItems[3]}</div>

                ${nextBtn('cook')}
              </div>


              <div id="tour-slide-cook" class="slide">
                <div class="bubble">${cookBubble}</div>

                <div class="split-hor">
                  <div>
                    ${cookItems[0]}
                  </div>
                  <div>
                    <div class="tour-img" id="img-cook"></div>
                  </div>
                </div>

                <div class="tour-remark">
                  ${cookItems[1]}

                  <div class="tour-img" id="img-cook-btn"></div>
                </div>

                ${nextBtn('reorganize')}
              </div>


              <div id="tour-slide-reorganize" class="slide">
                <div class="bubble">${reorganizeBubble}</div>

                <div class="tour-remark">
                  ${reorganizeItems[0]}

                  <div class="tour-img" id="img-reorganize-btn"></div>
                </div>

                <div class="split-hor centered">
                  <div>
                    ${reorganizeItems[1]}
                  </div>
                  <div>
                    <div class="tour-img" id="img-reorganize"></div>
                  </div>
                </div>

                <div class="split-hor centered">
                  <div>
                    <div class="tour-img" id="img-reorganize-controls"></div>
                  </div>
                  <div>
                    ${reorganizeItems[2]}
                  </div>
                </div>

                ${nextBtn('intro', _('Back'))}
              </div>


              <div id="tour-slide-bugs" class="slide">
                <div class="bubble">${bugBubble}</div>

                <ul>
                  <li>${bugItems[0]}</li>
                  <li>${bugItems[1]}</li>
                  <li>${bugItems[2]}</li>
                  <li>${bugItems[3]}</li>
                </ul>

                <a href="${bugUrl}" class="action-button bgabutton bgabutton_red">${bugReport}</a>

                ${nextBtn('intro', _('Back'))}
              </div>

            </div>
            <div id="tour-slide-footer">
              <input type="checkbox" id="neverShowMe" />
              ${neverShowMe}
            </div>
          `;
      },

      /***********************************
       ********* COMBO CHECKER ************
       ***********************************/
      onEnteringStateCheckCombos(args) {
        dojo.style('position-wrapper', 'display', 'none');
        if (!$('checkCombos')) {
          dojo.place("<div id='checkCombos'></div>", 'game_play_area');
        } else {
          dojo.empty('checkCombos');
        }

        let constructTable = (data, container) => {
          let firstRow = '<tr><th></th>';
          data.cards.forEach((card) => (firstRow += '<th>' + card.numbering + '</th>'));
          firstRow += '<tr>';
          dojo.place(firstRow, container);

          data.cards.forEach((card) => {
            let row = '<tr>';
            row += '<th>' + card.numbering + ' - ' + card.name + '</th>';
            data.cards.forEach((card2) => {
              let cId = card.id;
              let cId2 = card2.id;
              if (data.order[cId] && data.order[cId][cId2]) {
                row += '<td>' + data.order[cId][cId2] + '</td>';
              } else if (cId == cId2) {
                row += '<td>X</td>';
              } else {
                row += '<td></td>';
              }
            });
            row += '</tr>';
            dojo.place(row, container);
          });
        };

        dojo.place(
          '<div class="player-board checkCombos"><h1>Construct</h1><div id="checkCombosConstruct"></div><table id="tableCombosConstruct"></table></div>',
          'checkCombos',
        );
        args.construct.cards.forEach((card) => this.addCard(card, 'checkCombosConstruct'));
        constructTable(args.construct, 'tableCombosConstruct');

        dojo.place(
          '<div class="player-board checkCombos"><h1>Renovation</h1><div id="checkCombosRenovate"></div><table id="tableCombosRenovate"></table></div>',
          'checkCombos',
        );
        args.renovate.cards.forEach((card) => this.addCard(card, 'checkCombosRenovate'));
        constructTable(args.renovate, 'tableCombosRenovate');

        dojo.place(
          '<div class="player-board checkCombos"><h1>Improvement</h1><div id="checkCombosImprovement"></div><table id="tableCombosImprovement"></table></div>',
          'checkCombos',
        );
        args.improvement.cards.forEach((card) => this.addCard(card, 'checkCombosImprovement'));
        constructTable(args.improvement, 'tableCombosImprovement');
      },

      /***********************************
       ****** SNAKE OPENING HELPERS *******
       ***********************************/
      _setupSnakeOpeningHintObserver() {
        // The page title text is re-rendered by the BGA framework whenever
        // updatePageTitle is called. Anything placed inside it gets wiped.
        // Use a MutationObserver to re-attach the pill.
        const title = $('pagemaintitletext');
        if (!title || this._snakeOpeningHintObserver) return;
        const obs = new MutationObserver(() => {
          if (!this._isSnakeOpeningHintActive()) return;
          if ($('agr_snake_opening_hint')) return;
          this._renderSnakeOpeningHint();
        });
        obs.observe(title, { childList: true, subtree: false });
        this._snakeOpeningHintObserver = obs;
      },

      _isSnakeOpeningHintActive() {
        return !!this.gamedatas.snakeOpening && (this.gamedatas.turn || 0) <= 1;
      },

      _renderSnakeOpeningHint() {
        const title = $('pagemaintitletext');
        if (!title) return;

        const existing = $('agr_snake_opening_hint');
        if (existing) existing.remove();

        if (!this._isSnakeOpeningHintActive()) return;

        const hint = document.createElement('span');
        hint.id = 'agr_snake_opening_hint';

        const pill = document.createElement('span');
        pill.id = 'agr_snake_opening_pill';
        pill.className = 'agr-pill';
        pill.textContent = _('Snake opening');

        hint.appendChild(pill);
        title.appendChild(hint);

        this.addCustomTooltip('agr_snake_opening_pill', this._snakeOpeningTooltipHtml());
      },

      _snakeOpeningTooltipHtml() {
        const order = this.gamedatas.snakeTurnOrder || [];
        if (order.length === 0) return '';

        const renderRow = (ids, labelText) => {
          const names = ids.map((pId) => {
            const player = (this.gamedatas.players || {})[pId];
            const name = player ? player.name : `#${pId}`;
            const color = player ? `#${player.color}` : '#888';
            return `<span class="agr-snake-name" style="color:${color}">${name}</span>`;
          });
          return `<div class="agr-snake-row"><div class="agr-snake-row-label">${labelText}</div><div class="agr-snake-row-names">${names.join(' → ')}</div></div>`;
        };

        return `
          <div class="agr-snake-pop-title">${_('Snake opening')}</div>
          <div class="agr-snake-pop-desc">${_('Round 1 only: each player\u2019s second farmer is placed in reverse turn order.')}</div>
          ${renderRow(order, _('1st placement'))}
          ${renderRow(order.slice().reverse(), _('2nd placement'))}
        `;
      },

    },
  );
});
