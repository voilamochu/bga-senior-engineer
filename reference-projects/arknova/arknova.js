/**
 *------
 * BGA framework: © Gregory Isabelli <gisabelli@boardgamearena.com> & Emmanuel Colin <ecolin@boardgamearena.com>
 * Ark Nova implementation : © Timothée Pecatte <tim.pecatte@gmail.com>, Vincent Toper <vincent.toper@gmail.com>
 *
 * This code has been produced on the BGA studio platform for use on http://boardgamearena.com.
 * See http://en.boardgamearena.com/#!doc/Studio for more information.
 * -----
 *
 * arknova.js
 *
 * Ark Nova user interface script
 *
 * In this file, you are describing the logic of your user interface, in Javascript language.
 *
 */

var isDebug = window.location.host == 'studio.boardgamearena.com' || window.location.hash.indexOf('debug') > -1;
var debug = isDebug ? console.info.bind(window.console) : function () {};

define([
  'dojo',
  'dojo/_base/declare',
  g_gamethemeurl + 'modules/js/vendor/sortable.min.js',
  'ebg/core/gamegui',
  'ebg/counter',
  g_gamethemeurl + 'modules/js/Core/game.js',
  g_gamethemeurl + 'modules/js/Core/modal.js',
  g_gamethemeurl + 'modules/js/Core/core_patch_tooltip.js',
  g_gamethemeurl + 'modules/js/Players.js',
  g_gamethemeurl + 'modules/js/Cards.js',
  g_gamethemeurl + 'modules/js/ActionCards.js',
  g_gamethemeurl + 'modules/js/Meeples.js',
], function (dojo, declare, Sortable) {
  return declare(
    'bgagame.arknova',
    [customgame.game, ebg.core.core_patch_tooltip, arknova.players, arknova.cards, arknova.actionCards, arknova.meeples],
    {
      constructor() {
        this._activeStates = [
          'chooseActionCard',
          'cards',
          'discard',
          'build',
          'animals',
          'sponsors',
          'association',
          'resolveChoice',
          'confirmTurn',
          'confirmPartialTurn',
          'breakDiscard',
          'moveAnimals',
          'release',

          // effects
          'effectHunter',
          'effectPerception',
          'effectSnapping',
          'effectSunbathing',
          'effectPouch',
          'effectDigging',
          'effectPosturing',
          'effectPeacocking',
          'effectResistance',
          'effectClever',
          'effectPilfering',
          'effectPilferingExecute',
          'effectAssertion',
          'effectHypnosis',
          'effectScavenging',
          'effectBoost',
          'map4Effect',
          'effectMultiplier',
          'map10Effect',
          'map9Effect',

          // bonuses
          'takeInRange',
          'upgradeCard',
          'gainPartnerZoo',
          'gainUniversity',
          'buySponsor',
          'wazaSpecial',

          // MW
          'effectMark',
          'effectCutDown',
          'effectScubaDive',
          'effectSharkAttack',
          'effectGlide',
          'effectSymbiosis',
          'effectTrade',
          'effectExtraShift',
          'effectAdapt',
          'donate',
          'expedition',
          'searchPetDiscard',
          'reconstructionRemove',
          'reconstructionPlaceBack',
          'sponsorsDiscardCardGetBonus',
          'increaseSize',
          // MAP PACK 2
          'effectMap11Store',
          'effectMap11Unstore',
          'freePersonSponsor',
        ];
        this._notifications = [
          ['midmessage'],
          ['clearTurn'],
          ['refreshUI'],
          ['refreshHand'],
          ['updateInitialSelection'],
          ['updateInitialMapSelection'],
          ['updateInitialActionCardSelection'],
          ['updateInitialActionCardsKeep'],
          ['setupPlayer'],
          ['setupActionCards'],
          ['chooseActionCard'],
          ['actionCardCleanup'],
          ['pDrawCards'],
          ['drawCards', null, (notif) => notif.args.player_id == this.player_id],
          ['snapCard'],
          ['sponsorMagnet'],
          ['pDiscardCards', null],
          ['discardCards', null, (notif) => notif.args.player_id == this.player_id],
          ['discardCardsOnDisplay', null],
          ['fillPool'],
          ['buyAnimal'],
          ['playSponsor'],
          ['releaseAnimal'],
          ['getBonuses', null],
          ['buyBuilding'],
          ['increaseSize'],
          ['upgradeCard'],
          ['moveProjects'],
          ['addMeeples', null],
          ['slideMeeples', null],
          ['discardTokens', null],
          ['donation', null],
          ['moveAnimal'],
          ['pilferingMoney'],
          ['pilferingCard', null, (notif) => notif.args.player_id == this.player_id || notif.args.player_id2 == this.player_id],
          ['advanceBreak'],
          ['startBreak'],
          ['finishBreak'],
          ['updateBreakDiscardSelection'],
          ['enableMultiplier'],
          ['takeBonus'],
          ['hypnosis'],
          ['endOfGame'],
          ['finalScoring'],
          ['wazaSpecial'],
          ['removeActionCards'],
          ['pStoreCard'],
          ['storeCard', null, (notif) => notif.args.player_id == this.player_id],
          ['pUnstoreCard'],
          ['unstoreCard', null, (notif) => notif.args.player_id == this.player_id],

          // MW
          ['markCard', null],
          ['markAssign'],
          ['gainMarked', null],
          ['cutDown', null],
          ['reconstructionRemove'],
          ['reconstructionPlaceBack'],
        ];

        // Fix mobile viewport (remove CSS zoom)
        this.default_viewport = 'width=740';
        this.cardStatuses = {};
      },

      async notif_midmessage(n) {
        await this.wait(n.args.timer || 1000);
      },

      getSettingsSections() {
        return {
          layout: _('Layout'),
          playerBoard: _('Player Board/Panel'),
          gameFlow: _('Game Flow'),
          other: _('Other'),
        };
      },

      getSettingsConfig() {
        return {
          ////////////////////
          ///    LAYOUT    ///
          playerBoardsLayout: {
            default: 0,
            name: _('Player boards layout'),
            attribute: 'player-boards-layout',
            type: 'select',
            values: {
              0: _('Individual view (tabbed layout)'),
              1: _('Multiple view'),
            },
            section: 'layout',
          },
          twoColumnsLayout: {
            default: (isMobile) => (isMobile ? 1 : 0),
            name: _('Two columns layout'),
            attribute: 'two-columns',
            type: 'select',
            values: {
              0: _('Enabled'),
              1: _('Disabled (single column)'),
            },
            section: 'layout',
          },
          columnSizes: {
            default: 55,
            name: _('Column sizes'),
            type: 'slider',
            sliderConfig: {
              step: 3,
              padding: 0,
              range: {
                min: [40],
                max: [80],
              },
            },
            section: 'layout',
          },

          associationBoardScale: {
            default: 100,
            name: _('Association board scale'),
            type: 'slider',
            sliderConfig: {
              step: 5,
              padding: 0,
              range: {
                min: [30],
                max: [100],
              },
            },
            section: 'layout',
          },

          conservationTrack: {
            default: 2,
            name: _('Display first slots of conservation track'),
            type: 'select',
            values: {
              0: _('Never'),
              1: _('Only if my conservation is <= 10'),
              2: _("Only if at least one player's conservation is <= 10"),
              3: _('Always'),
            },
            section: 'layout',
          },

          handLocation: {
            default: (isMobile, isTouchDevice) => (isTouchDevice ? 1 : 0),
            name: _('Hand of cards'),
            type: 'select',
            values: {
              0: _('In a floating collapsible container'),
              3: _('In a floating collapsible container, opened when entering the table'),
              1: _('Above played cards'),
              2: _('Below played cards'),
            },
            section: 'layout',
          },
          cardScale: {
            default: 40,
            name: _('Card size in hand'),
            type: 'slider',
            sliderConfig: {
              step: 3,
              padding: 0,
              range: {
                min: [30],
                max: [80],
              },
            },
            section: 'layout',
          },

          associationBoardProjects: {
            default: 1,
            name: _('Projects on association board'),
            attribute: 'association-board-projects',
            type: 'select',
            values: {
              0: _('Full size'),
              1: _('Compacted'),
            },
            section: 'layout',
          },

          //////////////////////
          /// BOARD / PANELS ///
          playerPanelRows: {
            default: 1,
            name: _('Player panel informations'),
            attribute: 'panel-rows',
            type: 'select',
            values: {
              1: _('On a single row'),
              2: _('On two distinct rows'),
            },
            section: 'playerBoard',
          },
          playerIconSummary: {
            default: 2,
            name: _('Cards icons summary'),
            type: 'select',
            values: {
              0: _('Next to your zoo map'),
              1: _('In your player panel'),
              2: _('At both locations'),
            },
            section: 'playerBoard',
          },
          opponentIconSummary: {
            default: 2,
            name: _("Opponents' cards icons summaries"),
            type: 'select',
            values: {
              0: _('Next to their zoo map'),
              1: _('In their player panel'),
              2: _('At both locations'),
            },
            section: 'playerBoard',
          },
          soloTile: {
            default: 1,
            name: _('Solo tile'),
            attribute: 'solo-tile',
            type: 'select',
            values: {
              0: _('Original'),
              1: _('Compacted (horizontal)'),
            },
            section: 'playerBoard',
          },
          breakCounter: {
            default: 0,
            name: _('Break counter'),
            type: 'select',
            attribute: 'break-counter',
            values: {
              0: _('Show current step'),
              1: _('Show remaining steps'),
            },
            section: 'playerBoard',
          },

          //////////////////////
          ///// GAME FLOW //////
          chooseActionCard: {
            default: 1,
            name: _('Action card choosing process'),
            type: 'select',
            values: {
              0: _('Choose the card, then whether you want to use x-token(s) or gain one x-token'),
              1: _('Choose whether you want to use x-token(s), choose the card'),
            },
            section: 'gameFlow',
          },
          confirmMode: { type: 'pref', prefId: 103, section: 'gameFlow' },
          confirmUndoableMode: { type: 'pref', prefId: 104, section: 'gameFlow' },
          rotationArrowsLocation: {
            default: 0,
            name: _('Rotation arrows when building'),
            type: 'select',
            attribute: 'rotation-arrows',
            values: {
              0: _('Around the tile'),
              1: _('Inside the tile'),
              2: _('In the title bar only'),
            },
            section: 'gameFlow',
          },
          animalCardDesc: {
            default: 0,
            name: _("Cards' title and descriptions"),
            type: 'select',
            attribute: 'card-desc',
            values: {
              0: _('Enlarged, ability names only'),
              1: _('Closer to original assets'),
            },
            section: 'gameFlow',
          },
          restartButtons: {
            default: 1,
            name: _('Restart turn buttons'),
            type: 'select',
            attribute: 'undoButtons',
            values: {
              0: _('Only "Restart turn" button'),
              1: _('"Restart turn" and "Undo last step" buttons'),
              2: _('Only "Undo last step" button'),
            },
            section: 'gameFlow',
          },

          //////////////////////
          /////// OTHER ////////
          sortableHand: {
            default: (isMobile, isTouchDevice) => (isTouchDevice ? 1 : 0),
            name: _('Sortable hand using dragndrop'),
            type: 'select',
            values: {
              0: _('Enabled'),
              1: _('Disabled'),
            },
            section: 'other',
          },

          // grayedOut: {
          //   default: (isMobile, isTouchDevice) => (isTouchDevice ? 1 : 0),
          //   attribute: 'filter',
          //   name: _('Gray out unselectable cards'),
          //   type: 'select',
          //   values: {
          //     0: _('Enabled'),
          //     1: _('Disabled'),
          //   },
          //   section: 'other',
          // },

          snakeImages: { type: 'pref', prefId: 105, section: 'other' },
          reducedCost: { type: 'pref', prefId: 106, section: 'other' },
          helperPlayable: { type: 'pref', prefId: 110, section: 'other' },
          folderCost: { type: 'pref', prefId: 107, section: 'other' },
          enclosureSize: { type: 'pref', prefId: 108, section: 'other' },
          buildingsBorders: { type: 'pref', prefId: 109, section: 'other' },
          animationSpeed: { type: 'pref', prefId: 111, section: 'other' },
        };
      },

      isFloatingHand() {
        return [0, 3].includes(parseInt(this.settings.handLocation));
      },

      openHand() {
        if (this.isFloatingHand()) {
          $('floating-hand-wrapper').dataset.open = 'hand';
        }
      },

      openScoringHand() {
        if (this.isFloatingHand()) {
          $('floating-hand-wrapper').dataset.open = 'scoringHand';
        }
      },

      openStoredHand() {
        if (this.isFloatingHand()) {
          $('floating-hand-wrapper').dataset.open = 'storedHand';
        }
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
        dojo.place("<div id='anytimeActions' style='display:inline-block;float:right'></div>", $('generalactions'), 'after');
        // Create a new div for "subtitle"
        dojo.place("<div id='pagesubtitle'></div>", 'maintitlebar_content');

        // Attribute to know what asset we are using for max appeal
        $('ebd-body').dataset.startingAppeal = gamedatas.startingAppeal;
        // Attribute to know what asset we are using for association board (and other)
        $('ebd-body').dataset.marine = this.isMarine() ? 1 : 0;

        this.setupInfoPanel();
        this.setupAssociationBoard();
        this.setupPoolCardsContainer();
        this.setupScoreBoard();
        this.setupProjectsContainer();
        this.setupPlayers();
        this.setupCards();
        this.updateHandCards();
        this.setupBuildings();
        this.setupMeeples();
        this.updateActionCardsSummaries();
        this.updateLastRoundBanner();
        this.setupTour();
        this.setupSortableHand();
        this.updateCardCosts();
        this.inherited(arguments);
      },

      isMarine() {
        return this.gamedatas.isMarine || false;
      },

      // Generic automatic updating of infos
      updateInfosFromNotif(infos) {
        // Icons
        if (infos.icons) {
          Object.entries(infos.icons).forEach(([pId, icons]) => {
            this.gamedatas.players[pId].icons = icons;
          });
          this.updatePlayersIconsSummaries();
        }
        // Sizes (animals)
        if (infos.sizes) {
          Object.entries(infos.sizes).forEach(([pId, sizes]) => {
            this.gamedatas.players[pId].sizes = sizes;
          });
          this.updatePlayersIconsSummaries();
        }

        // Income
        if (infos.income) {
          Object.entries(infos.income).forEach(([pId, income]) => {
            this._playerCounters[pId]['income'].toValue(income);
          });
        }

        // Score
        if (infos.score) {
          Object.entries(infos.score).forEach(([pId, score]) => {
            this._scoreCounters[pId].toValue(score);
          });
        }

        // Map statuses
        if (infos.mapStatus) {
          Object.entries(infos.mapStatus).forEach(([pId, status]) => {
            this.gamedatas.players[pId].mapStatus = status;
          });
          this.updatePlayersMapStatuses();
        }

        // Hand statuses
        if (infos.handLimitStatus) {
          Object.entries(infos.handLimitStatus).forEach(([pId, status]) => {
            this.gamedatas.players[pId].handStatus = status;
          });
          this.updatePlayersHandStatuses();
        }

        // Veteranirian statuses
        if (infos.projectStrength) {
          Object.entries(infos.projectStrength).forEach(([pId, status]) => {
            this.gamedatas.players[pId].projectStrength = status;
          });
          this.updateConservationProjectStrength();
        }
      },

      setupSortableHand() {
        if ($(`hand-${this.player_id}`)) {
          let that = this;
          this._sortableHand = Sortable.create($(`hand-${this.player_id}`), {
            onStart(evt) {
              let cardId = evt.item.id;
              let tooltip = that.tooltips[cardId];
              tooltip.close();
              if (tooltip.showTimeout != null) clearTimeout(tooltip.showTimeout);
              that._dragndropMode = true;
            },

            onEnd(evt) {
              that._dragndropMode = false;
              let cardIds = that._sortableHand.toArray();
              that.takeAction('actOrderCards', { cardIds: JSON.stringify(cardIds), lock: false }, false);
            },

            fallbackTolerance: 10,
            delay: 200,
            delayOnTouchOnly: true,
            touchStartThreshold: 10,
          });
        }
      },

      onChangeSortableHandSetting(v) {
        if (this._sortableHand) this._sortableHand.option('disabled', v == 1);

        this.ensureNoSortableHandOnTouchDevice();
      },

      ensureNoSortableHandOnTouchDevice() {
        if (
          this.isTouchDevice &&
          this.settings &&
          this.settings.sortableHand == 0 &&
          this.isFloatingHand() &&
          this._sortableHand
        ) {
          this._sortableHand.option('disabled', true);

          this.showMessage(
            _(
              "Sortable hand with floating hand on touchscreen is disabled because it's buggy on many devices (can't click on card to select them). Sorry for the inconvenience."
            ),
            'info'
          );
        }
      },

      onLoadingComplete() {
        if (localStorage.getItem('arknovaTour') != 1) {
          if (!this.isReadOnly()) this.showTour();
        } else {
          if ($('tour-slide-footer')) {
            dojo.style('tour-slide-footer', 'display', 'none');
            $('neverShowMe').checked = true;
          }
        }

        this.updateLayout();
        this.inherited(arguments);
      },

      onScreenWidthChange() {
        if (this.settings) this.updateLayout();
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

          if ($(`dockedlog_${notif.mobileLogId}`))
            this.onClick($(`dockedlog_${notif.mobileLogId}`), () => this.undoToStep(stepId));
        }
      },

      undoToStep(stepId) {
        this.stopActionTimer();
        this.checkAction('actRestart');
        this.takeAction('actUndoToStep', { stepId }, false);
      },

      notif_clearTurn(n) {
        debug('Notif: restarting turn', n);
        this.cancelLogs(n.args.notifIds);
      },

      notif_refreshUI(n) {
        debug('Notif: refreshing UI', n);

        [
          'meeples',
          'players',
          'cards',
          'buildings',
          'break',
          'conservationBonuses',
          'endOfGame',
          'deckCount',
          'discardCount',
        ].forEach((value) => {
          this.gamedatas[value] = n.args.datas[value];
        });
        this.setupCards();
        this.setupMeeples();
        this.setupBuildings();
        this.updatePlayersCounters();
        this.updateActionCards();
        this.updateBreakCounter();
        this.updateScoreboardBonuses();
        this.updateLastRoundBanner();
        this.updateCardCosts();

        this._deckCounter.setValue(this.gamedatas.deckCount);
        this._discardCounter.setValue(this.gamedatas.discardCount);

        this.forEachPlayer((player) => {
          this._scoreCounters[player.id].toValue(player.newScore);
          this._playerCounters[player.id]['income'].toValue(player.income);
        });
      },

      notif_refreshHand(n) {
        debug('Notif: refreshing UI', n);
        this.gamedatas.players[n.args.player_id].hand = n.args.hand;
        this.updateHandCards();
        this.updateCardCosts();
      },

      notif_endOfGame() {
        debug('Notif: end of game');
        this.gamedatas.endOfGame = true;
        this.updateLastRoundBanner();
        return this.wait(1000);
      },

      onEnteringStateGameEnd(args) {
        if ($('last-round')) $('last-round').remove();
      },

      updateLastRoundBanner() {
        if (this.gamedatas.endOfGame) {
          if (!$('last-round')) {
            $('page-title').insertAdjacentHTML('beforeend', `<div id="last-round">${_('End of game triggered!')}</div>`);
          }
        } else {
          if ($('last-round')) {
            $('last-round').remove();
          }
        }
      },

      onUpdateActionButtons(stateName, args) {
        //        this.addPrimaryActionButton('test', 'test', () => this.testNotif());
        this.inherited(arguments);
      },

      testNotif() {},

      clearPossible() {
        dojo.empty('pagesubtitle');
        $('cards-pool').classList.remove('showFolderCosts');
        dojo.query('.selectedToMap10').removeClass('selectedToMap10');
        dojo.query('.selectedToDiscard').removeClass('selectedToDiscard');
        dojo.query('.selectedToKeep').removeClass('selectedToKeep');
        dojo.query('.selectedToMark').removeClass('selectedToMark');
        this.onHoverCell = null;
        this.onClickCell = null;

        let toRemove = ['building-controls', 'building-hover', 'btnRotateClockwise', 'btnRotateCClockwise'];
        toRemove.forEach((eltId) => {
          if ($(eltId)) $(eltId).remove();
        });

        this.closeChooseCardsModal();
        this.inherited(arguments);
      },

      onEnteringState(stateName, args) {
        debug('Entering state: ' + stateName, args);
        if (this.isFastMode() && !['draftPlayers'].includes(stateName)) return;
        $('ebd-body').dataset.state = stateName;

        if (args.args && args.args._private && args.args._private.statuses) {
          this.cardStatuses = args.args._private.statuses;
          this.updateCardStatuses();
        }

        if (args.args && args.args.showFolderCosts) {
          $('cards-pool').classList.add('showFolderCosts');
        }

        if (args.args && args.args.engine) {
          this.displayEngine(args.args.engine);
        }

        if (args.args && args.args.descSuffix) {
          this.changePageTitle(args.args.descSuffix);
        }
        if (args.args && args.args.description && args.args.descriptionmyturn) {
          this.gamedatas.gamestate.descriptionmyturn = args.args.descriptionmyturn;
          this.gamedatas.gamestate.description = args.args.description;
          this.updatePageTitle();
        }

        if (args.args && args.args.optionalAction) {
          let base = args.args.descSuffix ? args.args.descSuffix : '';
          this.changePageTitle(base + 'skippable');
        }

        if (args.args && args.args.source) {
          if (this.gamedatas.gamestate.descriptionmyturn.search('{source}') === -1) {
            if (args.args.sourceId) {
              let card = { id: args.args.sourceId };
              this.loadSaveCard(card);
              let uid = this.registerCustomTooltip(this.tplZooCard(card, true));

              $('pagemaintitletext').insertAdjacentHTML(
                'beforeend',
                ` (<span class="ark-log-card-name" id="${uid}">${_(args.args.source)}</span>)`
              );
              this.attachRegisteredTooltips();
            } else {
              $('pagemaintitletext').insertAdjacentHTML('beforeend', ` (${_(args.args.source)})`);
            }
          }
        }

        if (this._activeStates.includes(stateName) && !this.isCurrentPlayerActive()) return;

        if (args.args && args.args.optionalAction && !args.args.automaticAction) {
          this.addSecondaryActionButton(
            'btnPassAction',
            _('Pass'),
            () => this.takeAction('actPassOptionalAction'),
            'restartAction'
          );
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
              this.addDangerActionButton(
                'btnUndoLastStep',
                _('Undo last step'),
                () => this.undoToStep(lastStep),
                'restartAction'
              );
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
              msg = msg.log ? this.fsr(msg.log, msg.args) : _(msg);
              msg = this.formatString(msg);

              this.addPrimaryActionButton(
                'btnAnytimeAction' + i,
                msg,
                () => this.takeAction('actAnytimeAction', { id: i }, false),
                'anytimeActions'
              );
            });
          }
        }

        // Call appropriate method
        var methodName = 'onEnteringState' + stateName.charAt(0).toUpperCase() + stateName.slice(1);
        if (this[methodName] !== undefined) this[methodName](args.args);
      },

      //////////////////////////////
      //  ____  _             _
      // / ___|| |_ __ _ _ __| |_
      // \___ \| __/ _` | '__| __|
      //  ___) | || (_| | |  | |_
      // |____/ \__\__,_|_|   \__|
      //////////////////////////////

      notif_setupPlayer(n) {
        debug('Notif: finish setup of player', n);

        let player = this.gamedatas.players[n.args.player_id];

        // Action Cards
        player.actionCards = n.args.action_cards;
        this.updateActionCards();

        // Map
        let container = $(`player-board-${player.id}`);
        let previousMap = container.querySelector('.zoo-map');
        if (previousMap) previousMap.remove();

        player.mapId = n.args.mapId;
        $(`icons-summary-map-${player.id}`).insertAdjacentHTML('afterend', this.tplZooMap(MAPS_DATA[player.mapId], player));
        this.activateShowBuildingHelperButtons();
        this.setupChangeBoardArrows(player.id);
        this.specialSetupPlayer(player);

        // Meeples
        n.args.meeples.forEach((meeple) => this.addMeeple(meeple));

        // Buildings (for map A)
        n.args.buildings.forEach((building) => this.addBuilding(building));

        // Worker counter
        this._playerCounters[player.id]['worker'] = this.createCounter(`counter-${player.id}-worker`, 0);
        this.updateWorkerCounters();

        return this.wait(1200);
      },

      notif_setupActionCards(n) {
        debug('Notif: setup action cards', n);
        this.empty('arknova-draft-pool');
        let player = this.gamedatas.players[n.args.player_id];

        // Action Cards
        player.actionCards = n.args.action_cards;
        this.updateActionCards();

        return this.wait(1200);
      },

      /**
       * ZOO MAPS
       */
      onEnteringStateInitialMapSelection(args) {
        if (!args._private) return;
        let selection = null;

        let selectMap = (mapId) => {
          if (selection !== null && selection == mapId) return;

          let container = $(`player-board-${this.player_id}`);
          let previousMap = container.querySelector('.zoo-map');
          if (previousMap) previousMap.remove();

          $(`icons-summary-map-${this.player_id}`).insertAdjacentHTML('afterend', this.tplZooMap(MAPS_DATA[mapId]));
          this.activateShowBuildingHelperButtons();
          $('pagesubtitle').innerHTML = this.formatString(_(MAPS_DATA[mapId].desc));
          this.attachRegisteredTooltips();

          // Highlight button
          if (selection !== null) {
            $(`selectMap${selection}`).classList.remove('selected');
          }
          selection = mapId;
          $(`selectMap${selection}`).classList.add('selected');

          // Add confirm button (only if choice is different from potential existing selection)
          this.addPrimaryActionButton('btnConfirmChoice', _('Confirm'), () =>
            this.takeAction('actSelectMap', { mapId: selection }, false)
          );
          if (args._private.selection == selection) {
            $('btnConfirmChoice').remove();
          }
        };

        let possibleMaps = args._private.maps;
        possibleMaps.forEach((mapId) => {
          this.addPrimaryActionButton(`selectMap${mapId}`, _('Map ') + mapId, () => selectMap(mapId));
        });

        // Already made a selection => allow to change its mind
        if (args._private.selection != null) {
          selectMap(args._private.selection);
        }
        // No selection yet => let the user click on any
        else {
          selectMap(args._private.maps[0]);
        }
      },

      notif_updateInitialMapSelection(n) {
        this.clearPossible();
        this.updatePageTitle();
        this.onEnteringStateInitialMapSelection(n.args.args);
      },

      /**
       * ACTION CARDS (MARINE WORLD)
       */
      onEnteringStateInitialActionCardsSelection(args) {
        if (!args._private) return;

        let t = args._private;
        let elements = {};
        $('arknova-draft-pool')
          .querySelectorAll('.ark-card.action-card')
          .forEach((e) => e.classList.add('old'));
        t.cards.forEach((card) => {
          card.type = card.name + card.number;
          card.id = card.type + '-draft';
          if (!$(`action-card-${card.id}`)) {
            let o = this.place('tplActionCard', card, 'arknova-draft-pool');
            this.addCustomTooltip(o.id, this.tplActionCardTooltip(card), { midSize: false });
          }
          elements[card.type] = $(`action-card-${card.id}`);
          elements[card.type].classList.remove('old');
        });
        $('arknova-draft-pool')
          .querySelectorAll('.ark-card.action-card.old')
          .forEach((e) => this.destroy(e));
        // Previous cards
        t.previous.forEach((card) => {
          card.type = card.name + card.number;
          card.id = card.type + '-draft';
          if (!$(`action-card-${card.id}`)) {
            let o = this.place('tplActionCard', card, 'arknova-draft-picked');
            this.addCustomTooltip(o.id, this.tplActionCardTooltip(card), { midSize: false });
          }
        });

        // Already made a selection => allow to cancel it
        if (args._private.selection != null) {
          this.addSecondaryActionButton('actCancelSelection', _('Cancel'), () =>
            this.takeAction('actCancelActionCardSelection', {}, false)
          );
          elements[args._private.selection].classList.add('selectedToKeep', 'selected');
        }
        // No selection yet => let the user click on it
        else {
          let selectedType = null,
            selectedElt = null;
          Object.entries(elements).forEach(([cardType, element]) => {
            this.onClick(element, () => {
              if (selectedType !== null) selectedElt.classList.remove('selectedToKeep', 'selected');
              selectedType = cardType;
              selectedElt = element;
              element.classList.add('selectedToKeep', 'selected');
              this.addPrimaryActionButton('btnConfirm', _('Confirm Keep'), () => {
                this.takeAction('actSelectActionCard', { cardType: selectedType });
              });
            });
          });
        }
      },

      notif_updateInitialActionCardSelection(n) {
        this.clearPossible();
        this.updatePageTitle();
        this.onEnteringStateInitialActionCardsSelection(n.args.args);
      },

      onEnteringStateInitialActionCardsKeep(args) {
        let t = args._private;
        let elements = {};
        $('arknova-draft-pool')
          .querySelectorAll('.ark-card.action-card')
          .forEach((e) => e.classList.add('old'));

        t.cards.forEach((card) => {
          card.type = card.name + card.number;
          card.id = card.type + '-draft';
          if (!$(`action-card-${card.id}`)) {
            let o = this.place('tplActionCard', card, 'arknova-draft-pool');
            this.addCustomTooltip(o.id, this.tplActionCardTooltip(card), { midSize: false });
          } else {
            $('arknova-draft-pool').insertAdjacentElement('beforeend', $(`action-card-${card.id}`));
          }
          elements[card.type] = $(`action-card-${card.id}`);
          elements[card.type].classList.remove('old');
        });
        $('arknova-draft-pool')
          .querySelectorAll('.ark-card.action-card.old')
          .forEach((e) => this.destroy(e));

        // Already made a selection => allow to cancel it
        if (args._private.selection != null) {
          this.addSecondaryActionButton('actCancelSelection', _('Cancel'), () =>
            this.takeAction('actCancelActionCardsKeep', {}, false)
          );

          args._private.selection.forEach((type) => elements[type].classList.add('selectedToKeep', 'selected'));
        }
        // No selection yet => let the user click on it
        else {
          this.onSelectN({
            elements,
            n: 2,
            class: 'selectedToKeep',
            updateCallback: (selectedElements) => {
              let selectedTypes = selectedElements.map((type) => type.substring(0, type.length - 1));
              t.cards.forEach((card) => {
                // Card is selected => nothing to do
                if (selectedElements.includes(card.type)) return;
                // Otherwise, make sure it's not same type as a selected one
                elements[card.type].classList.toggle('selectable', !selectedTypes.includes(card.actionType));
              });
            },
            callback: (selectedElements) => {
              this.takeAction('actKeepActionCards', { cardIds: JSON.stringify(selectedElements) });
            },
          });
        }
      },

      notif_updateInitialActionCardsKeep(n) {
        this.clearPossible();
        this.updatePageTitle();
        this.onEnteringStateInitialActionCardsKeep(n.args.args);
      },

      /**
       * ZOO CARDS
       */
      onEnteringStateInitialSelection(args) {
        if (!args._private) return;
        this.openHand();

        // Already made a selection => allow to cancel it
        if (args._private.selection != null) {
          this.addSecondaryActionButton('actCancelSelection', _('Cancel'), () =>
            this.takeAction('actCancelSelection', {}, false)
          );
          args._private.selection.forEach((cardId) => {
            $(`card-${cardId}`).classList.add('selectedToDiscard');
          });
        }
        // No selection yet => let the user click on it
        else {
          this.onSelectNCards(args._private.cards, {
            n: args._private.n,
            class: 'selectedToKeep',
            confirmText: _('Confirm Keep'),
            callback: (selectedElements, ignoredElements) =>
              this.takeAction('actSelect', { cardIds: JSON.stringify(ignoredElements) }),
          });
        }
      },

      notif_updateInitialSelection(n) {
        this.clearPossible();
        this.updatePageTitle();
        this.onEnteringStateInitialSelection(n.args.args);
      },

      ////////////////////////////////////////
      //  _____             _
      // | ____|_ __   __ _(_)_ __   ___
      // |  _| | '_ \ / _` | | '_ \ / _ \
      // | |___| | | | (_| | | | | |  __/
      // |_____|_| |_|\__, |_|_| |_|\___|
      //              |___/
      ////////////////////////////////////////

      addActionChoiceBtn(choice, disabled = false) {
        if ($('btnChoice' + choice.id)) return;

        let desc = this.translate(choice.description);
        desc = this.formatString(desc);

        // Add source if any
        let source = _(choice.source ? choice.source : '');
        if (choice.sourceId) {
          let card = { id: choice.sourceId };
          this.loadSaveCard(card);
          source = this.fsr('${card_name}', { i18n: ['card_name'], card_name: _(card.name), card_id: card.id });
        }

        if (source != '') {
          desc += ` (${source})`;
        }

        this.addSecondaryActionButton(
          'btnChoice' + choice.id,
          desc,
          disabled
            ? () => {}
            : () => {
                this.askConfirmation(choice.irreversibleAction, () => this.takeAction('actChooseAction', { id: choice.id }));
              }
        );
        if (disabled) {
          $(`btnChoice${choice.id}`).classList.add('disabled');
        }
        if (choice.description.args && choice.description.args.bonus_pentagon) {
          $(`btnChoice${choice.id}`).classList.add('withbonus');
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
          true
        );
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

      askConfirmation(warning, callback) {
        if (warning === false || this.prefs[104].value == 0) {
          callback();
        } else {
          //        let msg = warning === true ? _('drawing card(s) from the deck or the discard') : warning;
          let msg =
            warning === true
              ? _(
                  "If you take this action, you won't be able to undo past this step because you will either draw card(s) from the deck or the discard, or someone else is going to make a choice"
                )
              : warning;
          this.confirmationDialog(
            msg,
            // this.fsr(
            //   _("If you take this action, you won't be able to undo past this step because of the following reason: ${msg}"),
            //   { msg }
            // ),
            () => {
              callback();
            }
          );
        }
      },

      // Generic call for Atomic Action that encode args as a JSON to be decoded by backend
      takeAtomicAction(action, args, warning = false) {
        if (!this.checkAction(action)) return false;

        this.askConfirmation(warning, () =>
          this.takeAction('actTakeAtomicAction', { actionName: action, actionArgs: JSON.stringify(args) }, false)
        );
      },

      ///////////////////////////////////////
      //  _____  __  __           _
      // | ____|/ _|/ _| ___  ___| |_ ___
      // |  _| | |_| |_ / _ \/ __| __/ __|
      // | |___|  _|  _|  __/ (__| |_\__ \
      // |_____|_| |_|  \___|\___|\__|___/
      ///////////////////////////////////////
      onEnteringStateEffectVenomPay(args) {
        // Fake button only useful for map4
        this.addPrimaryActionButton('btnPay', _('Pay Venom'), () => {});
        $('btnPay').classList.add('disabled');
      },

      onEnteringStateTakeInRange(args) {
        // Draw from deck
        this.addPrimaryActionButton('btnDrawOneFromDeck', _('Draw one card from deck'), () =>
          this.takeAtomicAction('actDrawCard', [1], true)
        );

        this.onSelectNCards(
          args.cardIds,
          {
            n: 1,
            callback: (selectedItems) => this.takeAtomicAction('actTakeInRange', [selectedItems[0]]),
            updateCallback: (selectedItems) =>
              ($('btnDrawOneFromDeck').style.display = selectedItems.length > 0 ? 'none' : 'inline'),
            class: 'selectedToKeep',
            confirmText: _('Confirm Take'),
          },
          'pool'
        );
      },

      onEnteringStateEffectHunter(args) {
        this.onSelectNCards(args._private.cardIds, {
          n: 1,
          class: 'selectedToKeep',
          confirmText: _('Confirm Keep'),
          callback: (selectedItems) => this.takeAtomicAction('actHunter', [selectedItems[0]]),
        });
      },

      onEnteringStateEffectResistance(args) {
        this.onSelectNCards(
          args._private.cardIds,
          {
            n: 1,
            class: 'selectedToKeep',
            confirmText: _('Confirm Keep'),
            callback: (selectedElements) => this.takeAtomicAction('actResistance', [selectedElements[0]]),
          },
          'scoringHand'
        );
      },

      onEnteringStateEffectAssertion(args) {
        this.onSelectNCards(
          args._private.cardIds,
          {
            n: 1,
            callback: (selectedElements) => this.takeAtomicAction('actAssertion', [selectedElements[0]]),
            class: 'selectedToKeep',
            confirmText: _('Confirm Take'),
          },
          'choice'
        );
      },

      onEnteringStateEffectScavenging(args) {
        this.onSelectNCards(args._private.cardIds, {
          n: 1,
          callback: (selectedItems) => this.takeAtomicAction('actScavenging', [selectedItems[0]]),
          class: 'selectedToKeep',
          confirmText: _('Confirm Keep'),
        });
      },

      onEnteringStateEffectPerception(args) {
        let cardIds = args._private.cardIds;
        this.onSelectNCards(cardIds, {
          n: args.m,
          class: 'selectedToKeep',
          confirmText: _('Confirm Keep'),
          callback: (selectedElements, ignoredElements) => this.takeAtomicAction('actPerception', [ignoredElements]),
        });
      },

      onEnteringStateEffectSnapping(args) {
        this.prepareCardsForSelection(args.cardIds, 'pool');
        args.cardIds.forEach((cardId) =>
          this.onClick(`card-${cardId}`, () => {
            let card = { id: cardId };
            this.loadSaveCard(card);
            this.clientState('cardsTakeOption', _('Confirm taking ${card_name}'), {
              cardId,
              card_id: cardId,
              card_name: card.name,
              canSnap: true,
            });
          })
        );

        if (args.canRefill) {
          this.addPrimaryActionButton('btnRefill', _('Replenish'), () => this.takeAtomicAction('actReplenish', [], true));
        }
      },

      onEnteringStateEffectSnappingConfirm(args) {
        $(`card-${args.cardId}`).classList.add('selected', 'selectedToKeep');
        this.addPrimaryActionButton('btnConfirm', _('Confirm Snap'), () => this.takeAtomicAction('actSnapCard', [args.cardId]));
        this.addCancelStateBtn();
      },

      onEnteringStateEffectSunbathing(args) {
        this.onSelectNCards(args._private.cardIds, {
          n: args.n,
          class: 'selectedToKeep',
          confirmText: _('Confirm Sell'),
          optional: true,
          callback: (selectedElements) => this.takeAtomicAction('actSunbathing', [selectedElements]),
        });
      },

      onEnteringStateMap4Effect(args) {
        this.onSelectNCards(args._private.cardIds, {
          n: args.n,
          class: 'selectedToKeep',
          confirmText: _('Confirm Sell'),
          optional: true,
          callback: (selectedElements) => this.takeAtomicAction('actMap4', [selectedElements]),
        });
      },

      onEnteringStateEffectPouch(args) {
        this.openHand();
        let cards = {};
        args._private.cardIds.forEach((cardId) => (cards[cardId] = $(`card-${cardId}`)));
        this.onSelectN({
          elements: cards,
          n: args.n,
          class: 'selectedToKeep',
          confirmText: _('Confirm Pouch'),
          optional: true,
          callback: (selectedElements) => this.takeAtomicAction('actPouch', [selectedElements]),
        });
      },

      onEnteringStateEffectDigging(args) {
        this.openHand();
        let cards = {};
        args._private.cardIds.forEach((cardId) => (cards[cardId] = $(`card-${cardId}`)));
        this.onSelectN({
          elements: cards,
          n: 1,
          class: 'selectedToDiscard',
          confirmText: _('Confirm Discard'),
          optional: true,
          callback: (selectedElements) => this.takeAtomicAction('actDigging', [selectedElements], true),
        });
      },

      onEnteringStateEffectPosturing(args) {
        this.onEnteringStateBuild(args);
      },

      onEnteringStateEffectPeacocking(args) {
        this.onEnteringStateBuild(args);
      },

      onEnteringStateEffectClever(args) {
        this.makeActionCardsSelectable(args.cardIds, (cardId) => {
          this.takeAtomicAction('actClever', [cardId]);
        });
      },

      onEnteringStateEffectHypnosis(args) {
        if (args.pIds.length < 2) return;

        args.pIds.forEach((pId) => {
          this.addSecondaryActionButton(`btnChoose${pId}`, this.coloredPlayerName(this.gamedatas.players[pId].name), () =>
            this.takeAtomicAction('actHypnosis', [pId])
          );
        });
      },

      onEnteringStateEffectPilfering(args) {
        if (args.automaticAction) return;

        let selectionAppeal = null;
        let selectionConservation = null;
        let updateButtons = () => {
          [...$('customActions').querySelectorAll('.selected')].forEach((elt) => elt.classList.remove('selected'));
          if (selectionAppeal) $(`btnChooseAppeal${selectionAppeal}`).classList.add('selected');
          if (selectionConservation) $(`btnChooseConservation${selectionConservation}`).classList.add('selected');

          if (selectionAppeal == null && args.appealPIds.length > 0) return;
          if (selectionConservation == null && args.n == 2 && args.conservationPIds.length > 0) return;

          this.addPrimaryActionButton('btnConfirm', _('Confirm'), () =>
            this.takeAtomicAction('actPilfering', [selectionAppeal, selectionConservation])
          );
        };

        args.appealPIds.forEach((pId) => {
          this.addSecondaryActionButton(
            `btnChooseAppeal${pId}`,
            this.coloredPlayerName(this.gamedatas.players[pId].name) + this.formatIcon('appeal'),
            () => {
              selectionAppeal = pId;
              updateButtons();
            }
          );
        });

        args.conservationPIds.forEach((pId) => {
          this.addSecondaryActionButton(
            `btnChooseConservation${pId}`,
            this.coloredPlayerName(this.gamedatas.players[pId].name) + this.formatIcon('conservation'),
            () => {
              selectionConservation = pId;
              updateButtons();
            }
          );
        });
      },

      onEnteringStateEffectPilferingExecute(args) {
        args.possibleChoices.forEach((choice) => {
          this.addPrimaryActionButton(
            `btnChoice${choice}`,
            choice == 'money' ? this.fsr(_('Give <MONEY:${n}>'), { n: args.n }) : _('Give a random card from your hand'),
            () => this.takeAtomicAction('actPilferingExecute', [choice], choice == 'cards')
          );
        });
      },

      onEnteringStateEffectBoost(args) {
        this.addPrimaryActionButton('btnStr1', this.formatString('<STRENGTH:1>'), () => this.takeAtomicAction('actBoost', [1]));
        this.addPrimaryActionButton('btnStr5', this.formatString('<STRENGTH:5>'), () => this.takeAtomicAction('actBoost', [5]));
      },

      onEnteringStateEffectMultiplier(args) {
        let cardIds = Object.keys(args.cards).map((t) => parseInt(t));
        this.makeActionCardsSelectable(
          cardIds,
          (cardId) => {
            let type = $(`action-card-${cardId}`).dataset.type;
            this.takeAtomicAction('actMultiplier', [type]);
          },
          args.pId ? args.pId : null
        );
      },

      onEnteringStateMap9Effect(args) {
        args.continents.forEach((continent) => {
          this.addPrimaryActionButton('btnContinent' + continent, this.formatIcon(continent), () =>
            this.takeAtomicAction('actMap9', [continent])
          );
        });
      },

      onEnteringStateMap10Effect(args) {
        this.openHand();
        let selectedCard = null;
        let cards = args._private.cards;
        Object.keys(cards).forEach((cardId) => {
          this.onClick(`card-${cardId}`, () => {
            if (selectedCard) $(`card-${selectedCard}`).classList.remove('selectedToDiscard', 'selectedToMap10');
            selectedCard = cardId;
            let canUseEffect = cards[cardId];
            $(`card-${selectedCard}`).classList.add(canUseEffect ? 'selectedToMap10' : 'selectedToDiscard');

            if ($('btnConfirmChoice')) $('btnConfirmChoice').remove();
            if ($('btnConfirmChoice2')) $('btnConfirmChoice2').remove();

            if (canUseEffect) {
              this.addPrimaryActionButton('btnConfirmChoice', _("Confirm and use Rescue Station's effect"), () =>
                this.takeAtomicAction('actMap10', [selectedCard, true])
              );
            }
            this.addPrimaryActionButton('btnConfirmChoice2', _('Confirm discard'), () => {
              let callback = () => this.takeAtomicAction('actMap10', [selectedCard, false]);

              if (cards[selectedCard]) {
                this.confirmationDialog(
                  _('Are you sure you want to discard that card instead of placing it in your Rescue Station?'),
                  callback
                );
              } else {
                callback();
              }
            });
          });
        });
      },

      //////////////////////////////////////////////
      // MARINE WORLD
      //////////////////

      onEnteringStateEffectMark(args) {
        let cards = {};
        args.cardIds.forEach((cardId) => (cards[cardId] = $(`card-${cardId}`)));
        this.onSelectN({
          elements: cards,
          n: args.n,
          class: 'selectedToMark',
          confirmText: _('Confirm Mark'),
          callback: (selectedElements) => this.takeAtomicAction('actMark', [selectedElements]),
        });
      },

      onEnteringStateEffectTrade(args) {
        for (let i = 1; i <= Math.min(args.n, args.tradable.xtoken); i++) {
          this.addPrimaryActionButton(
            `btnToken${i}`,
            this.fsr(_('Trade ${i}<XTOKEN> for <MONEY:${money}>'), { i, money: 5 * i }),
            () => this.takeAtomicAction('actTrade', ['xtoken', 'money', i])
          );
        }

        for (let i = 1; i <= Math.min(args.n, args.tradable.money, 5 - args.tradable.xtoken); i++) {
          this.addPrimaryActionButton(
            `btnMoney${i}`,
            this.fsr(_('Trade <MONEY:${money}> for ${i}<XTOKEN>'), { i, money: 5 * i }),
            () => this.takeAtomicAction('actTrade', ['money', 'xtoken', i])
          );
        }

        // TRADE FOR REPUTATION (SPONSORS1)
        if (args.canGainReputation) {
          for (let i = 1; i <= Math.min(args.n, args.tradable.xtoken); i++) {
            this.addPrimaryActionButton(
              `btnTokenForRep${i}`,
              this.fsr(_('Trade ${i}<XTOKEN> for <REPUTATION:${rep}>'), { i, rep: i }),
              () => this.takeAtomicAction('actTrade', ['xtoken', 'reputation', i])
            );
          }

          for (let i = 1; i <= Math.min(args.n, args.tradable.money); i++) {
            this.addPrimaryActionButton(
              `btnMoneyForRep${i}`,
              this.fsr(_('Trade <MONEY:${money}> for <REPUTATION:${rep}>'), { rep: i, money: 5 * i }),
              () => this.takeAtomicAction('actTrade', ['money', 'reputation', i])
            );
          }
        }
      },

      onEnteringStateEffectSymbiosis(args) {
        const effects = args.effects;
        Object.entries(effects).forEach(([cardId, abilities]) => {
          this.onClick(`card-${cardId}`, () => {
            args.cardId = cardId;
            this.clientState('effectSymbiosisChooseEffect', _('Confirm the ability you want to copy'), args);
          });
        });
      },

      onEnteringStateEffectSymbiosisChooseEffect(args) {
        this.addCancelStateBtn();
        this.onEnteringStateEffectSymbiosis(args);
        $(`card-${args.cardId}`).classList.add('selected');

        let selectedEffect = null;
        let selectEffect = (ability) => {
          if (selectedEffect) $(`btn${selectedEffect}`).classList.remove('selected');
          selectedEffect = ability;
          $(`btn${selectedEffect}`).classList.add('selected');
          $('btnConfirm').classList.remove('disabled');
        };

        let abilities = Object.entries(args.effects[args.cardId]);
        abilities.forEach(([ability, n]) => {
          let desc = this.getAbilityDesc(ability, n);
          this.addSecondaryActionButton(`btn${ability}`, desc.title, () => selectEffect(ability));
          this.addCustomTooltip(`btn${ability}`, desc.desc);
        });
        this.addPrimaryActionButton('btnConfirm', _('Confirm'), () => {
          if ($('btnConfirm').classList.contains('disabled')) return;

          this.takeAtomicAction('actSymbiosis', [args.cardId, selectedEffect]);
        });
        $('btnConfirm').classList.add('disabled');

        if (abilities.length == 1) {
          selectEffect(abilities[0][0]);
        }
      },

      onEnteringStateEffectExtraShift(args) {
        const btnMsgs = {
          2: _('From reputation zone'),
          3: _('From partner zoo zone'),
          4: _('From university zone'),
          5: _('From conservation project zone'),
        };

        args.slots.forEach((strength) => {
          this.onClick(`association_${strength}`, () => this.takeAtomicAction('actExtraShift', [strength]));
          this.addPrimaryActionButton(`btnAssociation${strength}`, btnMsgs[strength], () =>
            this.takeAtomicAction('actExtraShift', [strength])
          );
        });
      },

      onEnteringStateEffectGlide(args) {
        let cards = this.prepareCardsForSelection(args._private.cardIds);
        this.onSelectN({
          elements: cards,
          n: args.n,
          optional: true,
          confirmText: _('Confirm Discard'),
          callback: (selectedElements) => this.takeAtomicAction('actGlide', [selectedElements]),
        });
      },

      onEnteringStateEffectSharkAttack(args) {
        let cards = this.prepareCardsForSelection(args.cardIds, 'pool');
        this.onSelectN({
          elements: cards,
          n: args.n,
          optional: true,
          confirmText: _('Confirm Discard'),
          callback: (selectedElements) => this.takeAtomicAction('actSharkAttack', [selectedElements]),
        });
      },

      onEnteringStateEffectCutDown(args) {
        let selectedBuildingId = null;
        Object.entries(args.enclosures).forEach(([buildingId, size]) => {
          this.onClick(`building-${buildingId}`, () => {
            if (selectedBuildingId != null) {
              $(`building-${selectedBuildingId}`).classList.remove('selected');
            }
            selectedBuildingId = buildingId;
            $(`building-${selectedBuildingId}`).classList.add('selected');

            this.addPrimaryActionButton('btnConfirmEnclosure', _('Confirm'), () => {
              this.takeAtomicAction('actCutDown', [selectedBuildingId]);
            });
          });
        });
      },

      onEnteringStateEffectScubaDive(args) {
        this.onSelectNCards(args._private.cardIds, {
          n: 1,
          class: 'selectedToKeep',
          confirmText: _('Confirm Keep'),
          callback: (selectedItems) => this.takeAtomicAction('actScubaDive', [selectedItems[0]]),
        });
      },

      onEnteringStateEffectAdapt(args) {
        this.onSelectNCards(
          args._private.cardIds,
          {
            n: args.n,
            class: 'selectedToDiscard',
            confirmText: _('Confirm Discard'),
            callback: (selectedElements) => this.takeAtomicAction('actAdapt', [selectedElements]),
          },
          'scoringHand'
        );
      },

      onEnteringStateExpedition(args) {
        let cards = this.prepareCardsForSelection(args.cardIds, 'sponsors');
        this.onSelectN({
          elements: cards,
          n: 1,
          class: 'selectedToDiscard',
          confirmText: _('Confirm Dicard'),
          callback: (selectedElements) => this.takeAtomicAction('actExpedition', [selectedElements[0]]),
        });
      },

      onEnteringStateSearchPetDiscard(args) {
        let cards = this.prepareCardsForSelection(args._private.petIds, 'choice');
        $('popin_chooseCards').insertAdjacentHTML('beforeend', '<div id="discard-other-content"></div>');
        args._private.cardIds.forEach((cardId) => {
          if (args._private.petIds.includes(cardId)) return;

          let card = { id: cardId };
          this.loadSaveCard(card);
          this.addZooCard(card, 'discard-other-content');
          let oCard = $(`card-${cardId}`);
          oCard.classList.add('unselectable');
        });

        if (args._private.optional) {
          this.addPrimaryActionButton(
            'btnPass',
            _('Pass (no petting zoo animal in discard)'),
            () => this.takeAtomicAction('actPassSearchPetDiscard', []),
            'choose-cards-footer'
          );
        } else {
          this.onSelectN({
            elements: cards,
            n: 1,
            class: 'selectedToKeep',
            btnContainer: 'choose-cards-footer',
            confirmText: _('Confirm'),
            callback: (selectedElements) => this.takeAtomicAction('actSearchPetDiscard', [selectedElements[0]]),
          });
        }
      },

      onEnteringStateReconstructionRemove(args) {
        let elements = {};
        args.buildingIds.forEach((buildingId) => (elements[buildingId] = $(`building-${buildingId}`)));
        this.onSelectN({
          elements,
          n: 3,
          optional: true,
          confirmText: _('Confirm removal'),
          callback: (selectedElements) => this.takeAtomicAction('actReconstructionRemove', [selectedElements]),
        });
      },

      onEnteringStateReconstructionPlaceBack(args) {
        this.onEnteringStateBuild(args, 'reconstruction');
      },

      onEnteringStateIncreaseSize(args) {
        this.onEnteringStateBuild(args, 'increaseSize');
      },

      // MAP PACK 2
      onEnteringStateEffectMap11Store(args) {
        this.onSelectNCards(args._private.cardIds, {
          class: 'selectedToKeep',
          confirmText: _('Confirm card to store'),
          n: 1,
          callback: (selectedElements) => {
            this.takeAtomicAction('actMap11EffectStore', [selectedElements[0]]);
          },
        });
      },

      onEnteringStateEffectMap11Unstore(args) {
        this.onSelectNCards(
          args._private.cardIds,
          {
            class: 'selectedToKeep',
            confirmText: _('Confirm card to unstore'),
            n: 1,
            callback: (selectedElements) => {
              this.takeAtomicAction('actMap11EffectUnstore', [selectedElements[0]]);
            },
          },
          'storedHand'
        );
      },

      ////////////////////////////////////////////
      //  ____
      // | __ )  ___  _ __  _   _ ___  ___  ___
      // |  _ \ / _ \| '_ \| | | / __|/ _ \/ __|
      // | |_) | (_) | | | | |_| \__ \  __/\__ \
      // |____/ \___/|_| |_|\__,_|___/\___||___/
      ////////////////////////////////////////////
      formatBonus(bonus, bonusType = 'bonusTile', tooltip = true) {
        let iconsWithText = ['appeal', 'money', 'reputation', 'conservation', 'Adapt', 'CutDown', 'SharkAttack', 'Scavenging'];
        let type = Object.keys(bonus)[0],
          n = iconsWithText.includes(type) ? bonus[type] : null;
        if (type == 'FullThroated') type = 'add-worker';
        if (type == 'ExtraShift') type = 'extra-shift';
        if (type == 'AnimalMagnet') type = 'animal-magnet';
        if (type == 'CutDown') type = 'cut-down';
        if (type == 'SharkAttack') type = 'shark-attack';
        if (type == 'SEARCH_CARD') type = `search-${bonus[type]}`;
        if (type == 'special-enclosures-aquarium') type = 'special-enclosures';

        if (
          ['xtoken', 'Clever', 'Pouch', 'take-in-range', 'take-in-deck', 'take-in-range-or-deck'].includes(type) &&
          bonus[type] > 1
        ) {
          n = bonus[type];
        }

        // Icons without background
        let fullIcons = [
          'bonus-ignore-conditions',
          'bonus-extra-shift',
          'bonus-increased-hand',
          'bonus-kiosk-pavilion',
          'bonus-scoring-cards',
          'bonus-icon',
          'bonus-sponsor-gray',
          'search-sponsor-person',
        ];
        if (fullIcons.includes(type)) {
          n = null;
          bonusType = 'no-background';
        }
        if (type == 'strength') {
          bonusType = 'strength'; // MAP12
        }

        let iconType = bonusType == 'noBonus' ? 'icon-no-bonus' : 'icon-bonus';

        let iconHTML = `<div class='arknova-bonus arknova-icon ${iconType} ${bonusType}-type'>
            ${this.formatIcon(type, n)}
          </div>`;

        if (type == 'multiple') {
          iconHTML =
            '<div class="arknova-bonus bonus-multiple">' +
            bonus[type].map((b) => this.formatBonus(b, 'bonusTile', false)).join('') +
            '</div>';
        }

        if (!tooltip) {
          return `<div class='arknova-bonus-container'>${iconHTML}</div>`;
        }

        // Tooltip
        let desc = this.getBonusDescription(bonus);
        let id = this.registerCustomTooltip(
          type == 'DISCARD_SCORING'
            ? desc
            : `<div class='bonus-tooltip'>
        <div class='arknova-bonus-container'>${iconHTML}</div>
        ${desc}
      </div>`
        );

        return `<div id="${id}" class='arknova-bonus-container'>${iconHTML}</div>`;
      },

      getBonusDescription(bonus) {
        let type = Object.keys(bonus)[0],
          n = bonus[type];
        if (type == 'FullThroated') type = 'add-worker';

        let descs = {
          money: [_('Gain ${n} money.')],
          xtoken: [_('Gain ${n} X-tokens.'), _('Gain one X-token.')],
          'take-in-range': [_('${n} times : take 1 card within reputation range'), _('Take 1 card within reputation range.')],
          'take-in-deck': [_('Draw ${n} cards from the deck'), _('Draw 1 card from the deck.')],
          'take-in-range-or-deck': [
            _('${n} times : take 1 card within reputation range or draw 1 card from the deck'),
            _('Take 1 card within reputation range or draw 1 card from the deck.'),
          ],
          Clever: [
            _('Up to ${n} times: after finishing you may place any Action card on card slot 1. (Clever animal ability)'),
            _('After finishing you may place any Action card on card slot 1. (Clever animal ability)'),
          ],
          Determination: ['', _('After finishing you may perform another action. (Determination animal ability)')],
          reputation: [_('Increase your reputation by ${n}.')],
          Snapping: [_('Snapping: Take any 1 card from the display.')],
          'bonus-sponsor': [
            _(
              'You may play a Sponsor card from your hand by paying X money, where X is the level of the card. The usual rules apply. This means, you need to fulfill the conditions. Your Sponsors Action card stays in the same slot and is not moved by this effect.'
            ),
          ],
          'size-2': [_('You may once and immediately build a 2-space standard enclosure for free.')],
          'size-3': [_('You may once and immediately build a 3-space standard enclosure for free.')],
          'upgrade-card': [
            _('Upgrade any 1 of your Action cards. Flip it from side I to side II . It stays on the same card slot.'),
          ],
          conservation: [_('Increase your conservation by ${n}.')],
          appeal: [_('Increase your appeal by ${n}.')],
          'add-worker': [_('New association worker. (Animal ability Full-throated)')],
          'Partner-Zoo': [
            _(
              "Choose an available partner zoo from the Association board. You can't take a third partner zoo with this tile if your Association card is not yet upgraded."
            ),
          ],
          Fac: [_('Choose an available university from the Association board.')],
          'special-enclosures': [
            _(
              'You can build either the Reptile House or the Large Bird Aviary for free, even if you did not upgrade the Build action card yet.'
            ),
          ],
          'special-enclosures-aquarium': [
            _(
              'You can build either the Reptile House, the Large Bird Aviary or the Large Aquarium (when playing with Marine World) for free, even if you did not upgrade the Build action card yet.'
            ),
          ],
          kiosk: [_('You may build a kiosk for free. Usual kiosk constraints still apply.')],
          Multiplier: [_('Place a multiplier token on one of your action cards.')],
          DISCARD_SCORING: [
            _('As soon as anyone hit 10 conservation points, all the players must immediately discard 1 scoring card'),
          ],
          Pouch: [_('You may place ${n} cards from your hand under this card to gain <APPEAL:2> (each).')],
          map9: [_('Remove one continent marker to gain 1 bonus')],
          map10: [
            _(
              'Digging 1: EITHER discard 1 card from the display and replenish OR discard 1 card from your hand and draw 1 other from the deck. If the discarded card is an animal card (except for a Petting Zoo animal), you may slide it underneath your zoo map at one of the 3 slots that is empty. The animal counts as "in your zoo" from that moment on and triggers any other card that looks for icons or animals being played into your zoo. Ignore the rest of the card.'
            ),
          ],
          'bonus-ignore-conditions': [_('You may ignore up to 3 conditions on 1 Animal card when playing it. Single use.')],
          'bonus-extra-shift': [
            _(
              'Return 1 of your association workers from the Association board to the note pad on your zoo map (Animal ability Extra shift). Single use.'
            ),
          ],
          'bonus-increased-hand': [
            _(
              'Immediately and once Snap 1 card. Ongoing effect: your hand card limit is increased by 1. (Either to 4 or 6, depending on if you have the university increasing your hand limit.)'
            ),
          ],
          'bonus-kiosk-pavilion': [_('Place 3 free kiosks/pavilions in any combination (Animal ability Posturing).')],
          'bonus-scoring-cards': [
            _('Draw 3 new Final Scoring cards, then discard 3 Final Scoring cards (Animal ability Adapt).'),
          ],
          'bonus-icon': [_('You may use this tile as any icon when supporting a base conservation project. Single use.')],
          'bonus-sponsor-gray': [
            _(
              'Single use. You may play a Sponsor card from your hand by paying X money, where X is the level of the card. The usual rules apply. This means, you need to fulfill the conditions. Your Sponsors Action card stays in the same slot and is not moved by this effect.'
            ),
          ],
          Mark: [_('After finishing this action, mark 1 Animal card in the display.')],
          Scavenging: [_('Shuffle the discard pile and draw ${n} cards from it. Keep 1 and discard the others.')],
          'sponsor-person-card': [_('You may play a person Sponsor card from your hand for free')],
          SharkAttack: [
            _(
              'Discard ${n} Animal cards in reputation range from the display: Gain half the <APPEAL> of those cards (round down).'
            ),
          ],
          wave: [
            _(
              'When you cover this placement bonus, discard the leftmost card of the display (normally the one in folder 1) and replenish.'
            ),
          ],
          SEARCH_CARD: [
            _(
              'Reveal cards from the top of the deck until you reveal a person Sponsor card. Take this card into your hand and tuck all other revealed cards under the deck (without changing their order).'
            ),
          ],
          multiple: [
            _(
              'Gain 1 Conservation and reveal cards from the top of the deck until you reveal a person Sponsor card. Take this card into your hand and tuck all other revealed cards under the deck (without changing their order).'
            ),
          ],
          Adapt: [_('Draw ${n} new Final Scoring cards, then discard ${n} Final Scoring cards.')],
          CutDown: [_('You may remove 1 empty standard enclosure from your zoo map and gain back its cost.')],
          strength: [
            _('Conceal the leftmost open action strength. Action strength is increased to the next highest visible number.'),
          ],
          AnimalMagnet: [_('Add all Animal cards from the display into your hand.')],
          STORE: [
            _(
              'Place 1 card from your hand face down under an empty storage slot. Cards in a storage slot do not count as in your hand, so they do not count towards your hand card limit during a break and they are immune to effects like Pilfering. During the income step of each break, gain 2 money for each occupied storage slot on your zoo map.'
            ),
          ],
          ExtraShift: [_('You may return 1 of your association workers to your notepad.')],
        };

        let desc = descs[type] ?? ['TODO ' + type];
        let result = n == 1 && desc.length > 1 ? desc[1] : desc[0];

        if (n !== null) {
          result = this.fsr(result, { n, icon: this.formatIcon(`action-card-${n}`) });
        } else {
          result = this.formatString(result);
        }
        return result;
      },

      onEnteringStateUpgradeCard(args) {
        let cardIds = args.actionCardIds;
        this.makeActionCardsSelectable(cardIds, (cardId) => this.takeAtomicAction('actUpgradeCard', [cardId]));
      },

      onEnteringStateGainPartnerZoo(args) {
        args.meeples.forEach((meepleId) => {
          let meeple = $(`meeple-${meepleId}`);
          let callback = () => this.takeAtomicAction('actGainPartnerZoo', [meepleId]);
          this.onClick(meeple, callback);
          this.addPrimaryActionButton(`btnChoice${meepleId}`, this.formatIcon(meeple.dataset.type, null, false), callback);
        });
      },

      onEnteringStateGainUniversity(args) {
        args.meeples.forEach((meepleId) => {
          let meeple = $(`meeple-${meepleId}`);
          let callback = () => this.takeAtomicAction('actGainUniversity', [meepleId]);
          this.onClick(meeple, callback);
          this.addPrimaryActionButton(`btnChoice${meepleId}`, this.formatIcon(meeple.dataset.type, null, false), callback);
        });
      },

      onEnteringStateBuySponsor(args) {
        this.onSelectNCards(args._private.cardIds, {
          class: 'selectedToKeep',
          confirmText: _('Confirm Play'),
          n: 1,
          callback: (selectedElements) => {
            this.takeAtomicAction('actBuySponsor', [selectedElements[0]]);
          },
        });
      },

      onEnteringStateWazaSpecial(args) {
        this.addPrimaryActionButton('btnSmallAnimal', _('Small animals'), () =>
          this.askConfirmation(true, () => this.takeAtomicAction('actWazaSpecial', ['small']))
        );
        this.addPrimaryActionButton('btnLargeAnimals', _('Large animals'), () =>
          this.askConfirmation(true, () => this.takeAtomicAction('actWazaSpecial', ['large']))
        );
      },

      onEnteringStateFreePersonSponsor(args) {
        this.onSelectNCards(args._private.cardIds, {
          class: 'selectedToKeep',
          confirmText: _('Confirm Play'),
          n: 1,
          callback: (selectedElements) => {
            this.takeAtomicAction('actFreePersonSponsor', [selectedElements[0]]);
          },
        });
      },

      ////////////////////////////////////////////////////////////
      // _____                          _   _   _
      // |  ___|__  _ __ _ __ ___   __ _| |_| |_(_)_ __   __ _
      // | |_ / _ \| '__| '_ ` _ \ / _` | __| __| | '_ \ / _` |
      // |  _| (_) | |  | | | | | | (_| | |_| |_| | | | | (_| |
      // |_|  \___/|_|  |_| |_| |_|\__,_|\__|\__|_|_| |_|\__, |
      //                                                 |___/
      ////////////////////////////////////////////////////////////

      /**
       * Replace some expressions by corresponding html formating
       */
      formatIcon(name, n = null, lowerCase = true) {
        let type = lowerCase ? name.toLowerCase() : name;
        const BADGE_ICONS = [
          'africa',
          'europe',
          'asia',
          'americas',
          'australia',
          'bird',
          'predator',
          'herbivore',
          'bear',
          'reptile',
          'pet',
          'primate',
          'science',
          'partner',
          'seaanimal',
        ];
        if (BADGE_ICONS.includes(type)) {
          let ftype = type[0].toUpperCase() + type.slice(1);
          if (ftype == 'Seaanimal') ftype = 'SeaAnimal';

          return `<div class="icon-container icon-container-${type}">
        <div class="arknova-icon badge-icon" data-type="${ftype}"></div>
      </div>`;
        }

        const enclosureBonus = ['size-1', 'size-2', 'size-3'];
        if (enclosureBonus.includes(type)) {
          type = 'enclosure-' + type;
        }

        if (type == 'adapt') n = `${n}x&nbsp;&nbsp;${n}x`;

        const NO_TEXT_ICONS = ['xtoken', 'Clever', 'take-in-range', 'take-in-range-or-deck', 'take-in-deck'];
        let noText = NO_TEXT_ICONS.includes(name);
        let text = n == null ? '' : `<span>${n}</span>`;
        return `${noText ? text : ''}<div class="icon-container icon-container-${type}">
            <div class="arknova-icon icon-${type}">${noText ? '' : text}</div>
          </div>`;
      },

      formatString(str) {
        const ICONS = [
          'APPEAL',
          'CONSERVATION',
          'REPUTATION',
          'STRENGTH',
          'ACTION-CARDS',
          'ACTION-BUILD',
          'ACTION-ANIMALS',
          'ACTION-SPONSORS',
          'ACTION-ASSOCIATION',
          'ACTION-CARD',
          'ACTION-CARD-CARDS',
          'ACTION-CARD-BUILD',
          'ACTION-CARD-ANIMALS',
          'ACTION-CARD-SPONSORS',
          'ACTION-CARD-ASSOCIATION',
          'HERBIVORE',
          'MULTIPLIER',
          'ENCLOSURE-REGULAR',
          'ENCLOSURE-SIZE-2',
          'BONUS-SPONSOR',
          'MONEY',
          'XTOKEN',
          'BREAK',
          'STRBREAK',
          'SNAPPING',
          'ENCLOSURE-SPECIAL-LARGE-BIRD-AVIARY',
          'INCOME',
          'RESTAURANT',
          'HARBOR',
          'HOLLYWOOD',
          'INSTITUTE',
          'TOWER',
          'GATES',
          'CLEVER',
          'MAP9',
          'MAP10',
          'KIOSK-PAVILION',
          'AFRICA',
          'EUROPE',
          'ASIA',
          'AMERICAS',
          'AUSTRALIA',
          'PET',
          'REPTILE',
          'SEAANIMAL',
          'PRIMATE',
          'HERBIVORE',
          'BIRD',
          'PREDATOR',
          'KIOSK',
          'SEARCH-PRIMATE',
          'SEARCH-PREDATOR',
          'SEARCH-BIRD',
          'SEARCH-SEAANIMAL',
          'SEARCH-REPTILE',
          'SEARCH-HERBIVORE',
          'QUARTERS',
          'HAND-CARDS',
          'TAKE-IN-RANGE-OR-DECK',
          'TAKE-IN-RANGE',
          'TAKE-IN-RANGE-FOLDER-COST',
          'TAKE-IN-DECK',
          'DIGGING',
          'IGNORE-CONDITION',
          'HUNTER',
          'MARK',
          'BONUS-SPONSOR',
        ];

        ICONS.forEach((name) => {
          // WITH BONUS
          const regexBonus = new RegExp('<' + name + ':B:([^>]+)>', 'g');
          str = str.replaceAll(
            regexBonus,
            `<div class='arknova-bonus-container'>
            <div class='arknova-bonus arknova-icon icon-bonus bonusTile-type'>
              ${this.formatIcon(name, '$1')}
            </div>
          </div>`
          );
          // WITHOUT BONUS / WITH TEXT
          const regex = new RegExp('<' + name + ':([^>]+)>', 'g');
          str = str.replaceAll(regex, this.formatIcon(name, '$1'));
          // WITHOUT TEXT
          str = str.replaceAll(new RegExp('<' + name + '>', 'g'), this.formatIcon(name));
        });
        str = str.replace(/__([^_]+)__/g, '<span class="action-card-name-reference">$1</span>');
        str = str.replace(/\*\*([^\*]+)\*\*/g, '<b>$1</b>');

        // SIDE I/II action cards formater
        str = str.replaceAll(new RegExp('<SIDE_I>', 'g'), `<span class="side-I-format">${_('Side I:')}</span>`);
        str = str.replaceAll(new RegExp('<SIDE_II>', 'g'), `<span class="side-II-format">${_('Side II:')}</span>`);

        return str;
      },

      /**
       * Format log strings
       *  @Override
       */
      format_string_recursive(log, args) {
        try {
          if (log && args && !args.processed) {
            args.processed = true;

            log = this.formatString(_(log));

            if (args.card_name !== undefined && args.card_id !== undefined) {
              let card = { id: args.card_id };
              this.loadSaveCard(card);
              let uid = this.registerCustomTooltip(this.tplZooCard(card, true));
              args.card_name = `<span class="ark-log-card-name" id="${uid}">${_(args.card_name)}</span>`;
            }

            if (args.source !== undefined && args.sourceId !== undefined) {
              let card = { id: args.sourceId };
              this.loadSaveCard(card);
              let uid = this.registerCustomTooltip(this.tplZooCard(card, true));
              args.source = `<span class="ark-log-card-name" id="${uid}">${_(args.source)}</span>`;
            }

            if (args.action_card_icon !== undefined) {
              args.action_card_name = '';
              args.action_card_icon = this.formatIcon(`action-${args.action_card_type}`);
              args.action_card_level = `<span class='action-card-level level-${args.action_card_level}'>${args.action_card_level}</span>`;
            }

            if (args.strength_icon !== undefined) {
              args.strength_icon = this.formatIcon('strength', args.strength);
              args.strength = '';
            }

            if (args.strength_icon2 !== undefined) {
              args.strength_icon2 = this.formatIcon('strength', args.strength2);
              args.strength2 = '';
            }

            if (args.amount_money !== undefined) {
              args.amount_money = this.formatIcon('money', args.amount_money);
            }

            if (args.bonus_icon !== undefined) {
              args.bonus_icon = this.formatIcon(args.bonus_name, args.bonus);
              args.bonus_name = '';
              args.bonus = '';
            }

            if (args.bonus_pentagon !== undefined) {
              let data = {};
              data[args.bonus_type] = args.bonus_n;
              let type = args.bonus_source_type == 'incomeBonusSpace' ? 'income' : 'bonus';
              args.bonus_pentagon = this.formatBonus(data, type);
              args.bonus_raw_desc = '';
            }
          }
        } catch (e) {
          console.error(log, args, 'Exception thrown', e.stack);
        }

        let str = this.inherited(arguments);
        return this.formatString(str);
      },

      //////////////////////////////////////////////////////
      //  ___        __         ____                  _
      // |_ _|_ __  / _| ___   |  _ \ __ _ _ __   ___| |
      //  | || '_ \| |_ / _ \  | |_) / _` | '_ \ / _ \ |
      //  | || | | |  _| (_) | |  __/ (_| | | | |  __/ |
      // |___|_| |_|_|  \___/  |_|   \__,_|_| |_|\___|_|
      //////////////////////////////////////////////////////

      setupInfoPanel() {
        dojo.place(this.tplInfoPanel(), 'player_boards', 'first');
        this.addCustomTooltip('break-counter-icon', _('You get 1 X-token for reaching the last space of the Break track.'));
        let chk = $('help-mode-chk');
        dojo.connect(chk, 'onchange', () => this.toggleHelpMode(chk.checked));
        this.addTooltip('help-mode-switch', '', _('Toggle help/safe mode.'));

        this._deckCounter = this.createCounter('deck-counter', this.gamedatas.deckCount);
        this._discardCounter = this.createCounter('discard-counter', this.gamedatas.discardCount);

        this._settingsModal = new customgame.modal('showSettings', {
          class: 'arknova_popin',
          closeIcon: 'fa-times',
          title: _('Settings'),
          closeAction: 'hide',
          verticalAlign: 'flex-start',
          contentsTpl: `<div id='arknova-settings'>
             <div id='arknova-settings-header'></div>
             <div id="settings-controls-container"></div>
           </div>`,
        });

        this._breakCounter = this.createCounter('break-counter', this.gamedatas.break);
        this._breakModal = new customgame.modal('showBreak', {
          class: 'arknova_popin',
          closeIcon: 'fa-times',
          title: _('Break'),
          closeAction: 'hide',
          verticalAlign: 'flex-start',
          contentsTpl: this.getBreakModalContent(),
          scale: 0.9,
          breakpoint: 700,
        });
        $('round-counter-wrapper').addEventListener('click', () => {
          this._breakModal.show();
        });

        let handWrapper = $('floating-hand-wrapper');
        $('floating-hand-button').addEventListener('click', () => {
          if (handWrapper.dataset.open && handWrapper.dataset.open == 'hand') {
            delete handWrapper.dataset.open;
          } else {
            handWrapper.dataset.open = 'hand';
          }
        });
        $('floating-scoring-hand-button').addEventListener('click', () => {
          if (handWrapper.dataset.open && handWrapper.dataset.open == 'scoringHand') {
            delete handWrapper.dataset.open;
          } else {
            handWrapper.dataset.open = 'scoringHand';
          }
        });
      },

      tplInfoPanel() {
        let soloTile = '';
        for (let i = 1; i <= 7; i++) {
          soloTile += `<div class='solo-tile-slot' id='solo-tile-left-${i}'></div><div class='solo-tile-slot' id='solo-tile-right-${i}'></div>`;
        }
        return `
   <div class='player-board' id="player_board_config">
     <div id="player_config" class="player_board_content">
       <div class="player_config_row ${this.isSolo() ? 'solo' : ''}" id="round-counter-wrapper">
        <div id="break-counter-wrapper">
          <span id="break-counter-legend"></span>
          <span id='break-counter'></span><span id='break-counter-max'> / ${this.gamedatas.maxBreak}</span>
          <span id="break-counter-icon">${this.formatIcon('break')}${this.formatBonus({ xtoken: 1 }, 'bonusTile', false)}</span>
        </div>
        <div id="solo-tile">${soloTile}</div>
       </div>
       <div class="player_config_row">
         <div id="open-tour"></div>

         <div id="help-mode-switch">
           <input type="checkbox" class="checkbox" id="help-mode-chk" />
           <label class="label" for="help-mode-chk">
             <div class="ball"></div>
           </label><svg aria-hidden="true" focusable="false" data-prefix="fad" data-icon="question-circle" class="svg-inline--fa fa-question-circle fa-w-16" role="img" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 512 512"><g class="fa-group"><path class="fa-secondary" fill="currentColor" d="M256 8C119 8 8 119.08 8 256s111 248 248 248 248-111 248-248S393 8 256 8zm0 422a46 46 0 1 1 46-46 46.05 46.05 0 0 1-46 46zm40-131.33V300a12 12 0 0 1-12 12h-56a12 12 0 0 1-12-12v-4c0-41.06 31.13-57.47 54.65-70.66 20.17-11.31 32.54-19 32.54-34 0-19.82-25.27-33-45.7-33-27.19 0-39.44 13.14-57.3 35.79a12 12 0 0 1-16.67 2.13L148.82 170a12 12 0 0 1-2.71-16.26C173.4 113 208.16 90 262.66 90c56.34 0 116.53 44 116.53 102 0 77-83.19 78.21-83.19 106.67z" opacity="0.4"></path><path class="fa-primary" fill="currentColor" d="M256 338a46 46 0 1 0 46 46 46 46 0 0 0-46-46zm6.66-248c-54.5 0-89.26 23-116.55 63.76a12 12 0 0 0 2.71 16.24l34.7 26.31a12 12 0 0 0 16.67-2.13c17.86-22.65 30.11-35.79 57.3-35.79 20.43 0 45.7 13.14 45.7 33 0 15-12.37 22.66-32.54 34C247.13 238.53 216 254.94 216 296v4a12 12 0 0 0 12 12h56a12 12 0 0 0 12-12v-1.33c0-28.46 83.19-29.67 83.19-106.67 0-58-60.19-102-116.53-102z"></path></g></svg>
         </div>

         <div id="show-settings">
           <svg  xmlns="http://www.w3.org/2000/svg" viewBox="0 0 640 512">
             <g>
               <path class="fa-secondary" fill="currentColor" d="M638.41 387a12.34 12.34 0 0 0-12.2-10.3h-16.5a86.33 86.33 0 0 0-15.9-27.4L602 335a12.42 12.42 0 0 0-2.8-15.7 110.5 110.5 0 0 0-32.1-18.6 12.36 12.36 0 0 0-15.1 5.4l-8.2 14.3a88.86 88.86 0 0 0-31.7 0l-8.2-14.3a12.36 12.36 0 0 0-15.1-5.4 111.83 111.83 0 0 0-32.1 18.6 12.3 12.3 0 0 0-2.8 15.7l8.2 14.3a86.33 86.33 0 0 0-15.9 27.4h-16.5a12.43 12.43 0 0 0-12.2 10.4 112.66 112.66 0 0 0 0 37.1 12.34 12.34 0 0 0 12.2 10.3h16.5a86.33 86.33 0 0 0 15.9 27.4l-8.2 14.3a12.42 12.42 0 0 0 2.8 15.7 110.5 110.5 0 0 0 32.1 18.6 12.36 12.36 0 0 0 15.1-5.4l8.2-14.3a88.86 88.86 0 0 0 31.7 0l8.2 14.3a12.36 12.36 0 0 0 15.1 5.4 111.83 111.83 0 0 0 32.1-18.6 12.3 12.3 0 0 0 2.8-15.7l-8.2-14.3a86.33 86.33 0 0 0 15.9-27.4h16.5a12.43 12.43 0 0 0 12.2-10.4 112.66 112.66 0 0 0 .01-37.1zm-136.8 44.9c-29.6-38.5 14.3-82.4 52.8-52.8 29.59 38.49-14.3 82.39-52.8 52.79zm136.8-343.8a12.34 12.34 0 0 0-12.2-10.3h-16.5a86.33 86.33 0 0 0-15.9-27.4l8.2-14.3a12.42 12.42 0 0 0-2.8-15.7 110.5 110.5 0 0 0-32.1-18.6A12.36 12.36 0 0 0 552 7.19l-8.2 14.3a88.86 88.86 0 0 0-31.7 0l-8.2-14.3a12.36 12.36 0 0 0-15.1-5.4 111.83 111.83 0 0 0-32.1 18.6 12.3 12.3 0 0 0-2.8 15.7l8.2 14.3a86.33 86.33 0 0 0-15.9 27.4h-16.5a12.43 12.43 0 0 0-12.2 10.4 112.66 112.66 0 0 0 0 37.1 12.34 12.34 0 0 0 12.2 10.3h16.5a86.33 86.33 0 0 0 15.9 27.4l-8.2 14.3a12.42 12.42 0 0 0 2.8 15.7 110.5 110.5 0 0 0 32.1 18.6 12.36 12.36 0 0 0 15.1-5.4l8.2-14.3a88.86 88.86 0 0 0 31.7 0l8.2 14.3a12.36 12.36 0 0 0 15.1 5.4 111.83 111.83 0 0 0 32.1-18.6 12.3 12.3 0 0 0 2.8-15.7l-8.2-14.3a86.33 86.33 0 0 0 15.9-27.4h16.5a12.43 12.43 0 0 0 12.2-10.4 112.66 112.66 0 0 0 .01-37.1zm-136.8 45c-29.6-38.5 14.3-82.5 52.8-52.8 29.59 38.49-14.3 82.39-52.8 52.79z" opacity="0.4"></path>
               <path class="fa-primary" fill="currentColor" d="M420 303.79L386.31 287a173.78 173.78 0 0 0 0-63.5l33.7-16.8c10.1-5.9 14-18.2 10-29.1-8.9-24.2-25.9-46.4-42.1-65.8a23.93 23.93 0 0 0-30.3-5.3l-29.1 16.8a173.66 173.66 0 0 0-54.9-31.7V58a24 24 0 0 0-20-23.6 228.06 228.06 0 0 0-76 .1A23.82 23.82 0 0 0 158 58v33.7a171.78 171.78 0 0 0-54.9 31.7L74 106.59a23.91 23.91 0 0 0-30.3 5.3c-16.2 19.4-33.3 41.6-42.2 65.8a23.84 23.84 0 0 0 10.5 29l33.3 16.9a173.24 173.24 0 0 0 0 63.4L12 303.79a24.13 24.13 0 0 0-10.5 29.1c8.9 24.1 26 46.3 42.2 65.7a23.93 23.93 0 0 0 30.3 5.3l29.1-16.7a173.66 173.66 0 0 0 54.9 31.7v33.6a24 24 0 0 0 20 23.6 224.88 224.88 0 0 0 75.9 0 23.93 23.93 0 0 0 19.7-23.6v-33.6a171.78 171.78 0 0 0 54.9-31.7l29.1 16.8a23.91 23.91 0 0 0 30.3-5.3c16.2-19.4 33.7-41.6 42.6-65.8a24 24 0 0 0-10.5-29.1zm-151.3 4.3c-77 59.2-164.9-28.7-105.7-105.7 77-59.2 164.91 28.7 105.71 105.7z"></path>
             </g>
           </svg>
         </div>
       </div>
       <div class="player_config_row">
         <div id="open-scoreboard">
          <svg  xmlns="http://www.w3.org/2000/svg" viewBox="0 0 119.79425 66.99308">
            <path d="M 1.5,33.409858 V 1.5 h 8.3454309 8.3454311 l 0.163419,1.379818 c 0.265488,2.2416348 2.000466,4.6289557 4.185096,5.7586718 1.894316,0.9795889 5.24383,0.9795889 7.138145,0 2.138442,-1.105831 4.272475,-4.2320506 4.272968,-6.2596166 L 33.950703,1.5 h 8.383099 8.383098 v 31.909858 31.909859 h -8.383098 c -8.187748,0 -8.383099,-0.01255 -8.383099,-0.537943 0,-0.920584 -0.984513,-3.182657 -1.873914,-4.305619 -0.504652,-0.637175 -1.52315,-1.409737 -2.505979,-1.900862 -4.202927,-2.100226 -9.117141,-0.159431 -10.769259,4.253159 -0.294445,0.786424 -0.535354,1.668678 -0.535354,1.960563 0,0.515907 -0.233696,0.530702 -8.3830986,0.530702 H 1.5 Z" />
            <path d="m 85.834954,64.739661 c -4.174784,-1.45373 -7.008674,-3.03363 -10.684968,-5.95688 -4.585408,-3.64614 -8.95837,-9.92835 -12.038002,-17.29384 -1.873738,-4.48138 -4.058014,-13.2089 -4.162315,-16.630991 -0.02238,-0.734279 1.191073,-1.811107 5.194576,-4.60971 l 2.109751,-1.474797 -0.138231,-1.08169 c -0.07603,-0.59493 -0.453664,-2.420282 -0.839194,-4.056338 -0.38553,-1.636056 -0.769568,-3.475959 -0.853418,-4.0886722 -0.145067,-1.060046 -0.114909,-1.12058 0.622402,-1.249314 0.42617,-0.07441 3.695419,-0.892108 7.264995,-1.817109 3.569577,-0.925001 8.685961,-2.2340409 11.369742,-2.9089789 l 4.879602,-1.2271604 7.695046,1.9523824 c 4.23228,1.0738099 8.8511,2.2362139 10.26406,2.5831209 4.45947,1.094881 6.07839,1.540375 6.28676,1.729991 0.11124,0.101227 0.0374,0.847514 -0.16403,1.6584162 -0.20146,0.810902 -0.5784,2.447887 -0.83765,3.637747 -0.25925,1.189859 -0.58025,2.589295 -0.71333,3.109859 -0.13309,0.520563 -0.24265,1.079151 -0.24348,1.241307 -10e-4,0.262848 4.96946,4.133743 6.79599,5.292214 0.64469,0.408893 0.70128,0.577436 0.59071,1.759347 -0.33619,3.593666 -1.70154,9.383776 -3.24153,13.746566 -4.78474,13.55511 -13.69379,22.83108 -25.14917,26.18493 -1.376886,0.40312 -1.434186,0.39596 -4.008316,-0.5004 z" />
          </svg>    
         </div>
         <div id="deck-counter-holder">
            <div id="deck-counter">0</div>
         </div>
         <div id="discard-counter-holder">
            <div id="discard-counter">0</div>
            <div id="discard-counter-icon"><i class="fa fa-trash"></i></div>
          </div>
       </div>
     </div>
   </div>
   `;
      },

      updatePlayerOrdering() {
        this.inherited(arguments);
        dojo.place('player_board_config', 'player_boards', 'first');
      },

      onChangeColumnSizesSetting(val) {
        this.updateLayout();
      },

      onChangeColumnSizesSetting(val) {
        this.updateLayout();
      },

      onChangeAssociationBoardScaleSetting(val) {
        this.updateLayout();
      },

      onChangeTwoColumnsLayoutSetting(val) {
        this.updateLayout();
      },

      updateDuplicateConservationBoard() {
        let minConservation = Math.min(
          ...Object.keys(this.gamedatas.players).map((pId) => this._playerCounters[pId]['conservation'].getValue())
        );
        let myConservation = this.isSpectator ? 0 : this._playerCounters[this.player_id]['conservation'].getValue();
        let setting = this.settings ? this.settings.conservationTrack : 2;

        let current = $('ebd-body').dataset.conservationTrack;
        let newVal = 0;
        if (setting == 1 && myConservation <= 10) newVal = 1;
        else if (setting == 2 && minConservation <= 10) newVal = 1;
        else if (setting == 3) newVal = 1;

        if (current != newVal) {
          $('ebd-body').dataset.conservationTrack = newVal;
          if (this.settings) this.updateLayout();
        }
      },

      onChangeConservationTrackSetting() {
        this.updateDuplicateConservationBoard();
      },

      onChangeCardScaleSetting(val) {
        let scale = val / 100;
        [...document.querySelectorAll('.player-board-hand')].forEach((elt) => {
          elt.style.setProperty('--arkNovaZooCardScale', scale);
        });

        $('floating-hand-wrapper').style.setProperty('--arkNovaZooCardScale', scale);
      },

      updateLayout() {
        if (!this.settings) return;
        const ROOT = document.documentElement;
        let associationBoardScale = 1;

        const WIDTH = $('arknova-main-container').getBoundingClientRect()['width'] - 5;
        const LEFT_COLUMN = 926;
        const RIGHT_COLUMN = 700;

        if (this.settings.twoColumnsLayout == 0) {
          const size = this.settings.columnSizes;
          let proportions = [size, 100 - size];

          const LEFT_SIZE = (proportions[0] * WIDTH) / 100;
          const leftColumnScale = LEFT_SIZE / LEFT_COLUMN;
          ROOT.style.setProperty('--arkNovaLeftColumnScale', leftColumnScale);

          const RIGHT_SIZE = (proportions[1] * WIDTH) / 100;
          const rightColumnScale = RIGHT_SIZE / RIGHT_COLUMN;

          $('arknova-main-container').style.gridTemplateColumns = `${LEFT_SIZE}px ${RIGHT_SIZE}px`;

          associationBoardScale = (rightColumnScale * this.settings.associationBoardScale) / 100;
          const HEIGHT_POOL = 260 * leftColumnScale;
          const HEIGHT_ASSOCIATION = $('association-board-container').offsetHeight * associationBoardScale;
          const HEIGHT_ROW = Math.max(0, HEIGHT_ASSOCIATION - HEIGHT_POOL);
          const HEIGHT_PLAYER_BOARD = 650 * leftColumnScale;
          $('arknova-main-container').style.gridTemplateRows = `${HEIGHT_POOL}px ${HEIGHT_ROW}px ${
            HEIGHT_PLAYER_BOARD - HEIGHT_ROW
          }px`;
        } else {
          const LEFT_SIZE = WIDTH;
          const leftColumnScale = LEFT_SIZE / LEFT_COLUMN;
          ROOT.style.setProperty('--arkNovaLeftColumnScale', leftColumnScale);

          const RIGHT_SIZE = WIDTH;
          const rightColumnScale = RIGHT_SIZE / RIGHT_COLUMN;

          associationBoardScale = (rightColumnScale * this.settings.associationBoardScale) / 100;
        }

        ROOT.style.setProperty('--arkNovaAssociationBoardScale', associationBoardScale);
      },

      /////////////////////////////////
      //  ____                 _
      // | __ ) _ __ ___  __ _| | __
      // |  _ \| '__/ _ \/ _` | |/ /
      // | |_) | | |  __/ (_| |   <
      // |____/|_|  \___|\__,_|_|\_\
      /////////////////////////////////
      updateBreakCounter() {
        let breakVal = this.gamedatas.break;
        if (this.settings && this.settings.breakCounter == 1) {
          breakVal = this.gamedatas.maxBreak - breakVal;
        }
        this._breakCounter.toValue(breakVal);
      },

      onChangeBreakCounterSetting(val) {
        this.updateBreakCounter();
        $('break-counter-legend').innerHTML = val == 1 ? _('Remaining:') : '';
      },

      getBreakModalContent() {
        return `<div id='break-details'>
          ${_(
            'Ark Nova does not follow a fixed round system. You take turns, taking 1 turn at a time, until the Break token has advanced to the last space of the Break track.'
          )}
          ${_(
            'An Action card, Sponsor card, or Animal card effect could cause this to happen. If you trigger the break, complete your current turn, take the Break token, and then all players perform the following steps in order:'
          )}

          <div id='break-details-phases'>
            <div class='break-details-phase'>
              <h3>${_('1. Hand card limit:')}</h3>
              <div class='phase-image phase-1'></div>
              <div class='phase-desc'>
                ${_(
                  'If you have more cards in your hand than your hand limit allows, discard any surplus cards of your choice from your hand to the discard pile.'
                )}
                ${_('Normally your hand limit is 3, but a particular university allows you to hold 5.')}
              </div>
            </div>

            <div class='break-details-phase'>
              <h3>${_('2. Tokens on Action cards:')}</h3>
              <div class='phase-image phase-2'></div>
              <div class='phase-desc'>
                ${_(
                  'If you have Multiplier, Venom, and/or Constriction tokens on your Action cards, return them to the supply. They no longer have any effect.'
                )}
              </div>
            </div>

            <div class='break-details-phase'>
              <h3>${_('3. Association board:')}</h3>
              <div class='phase-image phase-3'></div>
              <div class='phase-desc'>
                ${_('Return all your association workers from the Association board to the notepad on your zoo map.')}
                ${_('They become available to you again as active workers.')}
                ${_(
                  'Replenish the display of partner zoos and universities so that exactly one of each partner zoo and university is now available again.'
                )}
              </div>
            </div>

            <div class='break-details-phase'>
              <h3>${_('4. Replenish display:')}</h3>
              <div class='phase-image phase-4'></div>
              <div class='phase-desc'>
                ${_(
                  'Discard the two bottom cards of the display (folders 1 and 2) to the discard pile, move the remaining cards down and replenish the display.'
                )}
              </div>
            </div>

            <div class='break-details-phase'>
              <h3>${_('5. Take income:')}</h3>
              <div class='phase-image phase-5'></div>
              <div class='phase-desc'>
                ${this.formatString(
                  _(
                    '**Collect income according to the appeal of your zoo**. Take the amount of money indicated next to your counter on the Appeal track.'
                  )
                )}
                <br />
                ${this.formatString(
                  _('**Collect income for the kiosks on your zoo map**. Check each kiosk on your zoo map separately.')
                )}
                ${_(
                  'Take 1 money for each unique building, special enclosure, occupied standard enclosure, and pavilion adjacent to it.'
                )}
                ${_(
                  'A building must share at least one side of one space with a kiosk in order to be considered adjacent to it.'
                )}
                ${_('An empty standard enclosure does not provide income (but an empty special enclosure does).')}
                <br />
                ${this.formatString(_('**Collect all the income indicated by the following icon**: <INCOME>'))}
                ${_('The icon appears on some Sponsor cards and in some bonuses on the left side of your zoo map.')}
                ${_(
                  'Take only the bonuses on your zoo map that you have already activated (those you have uncovered by placing the player token on a conservation project).'
                )}
                </div>
            </div>

            <div class='break-details-phase'>
              <h3>${_('6. Break track:')}</h3>
              <div class='phase-image phase-6'></div>
              <div class='phase-desc'>
                ${_('Return the Break token to the starting space for your player count.')}
              </div>
            </div>
          </div>
        </div>`;
      },

      async notif_advanceBreak(n) {
        debug('Notif: advancing break token', n);
        this.gamedatas.break = n.args.break;

        if (this.isFastMode()) {
          this.updateBreakCounter();
          return;
        }

        let tmpElt = `<div style='position:absolute' id='animation-break'>${this.formatIcon('break', n.args.n)}</div>`;
        this.getVisibleTitleContainer().insertAdjacentHTML('beforebegin', tmpElt);
        let mobileId = `animation-break`;
        let counterId = `break-counter`;

        await this.slide(mobileId, counterId, {
          from: this.getVisibleTitleContainer(),
          destroy: true,
          phantom: false,
          duration: this.getTiming(1200),
        });

        this.updateBreakCounter();
        if (n.args.bonuses) {
          await this.notif_getBonuses(n);
        }
      },

      async notif_startBreak(n) {
        debug('Notif: starting a break', n);
        if (this.isFastMode()) return;

        let dialog = new customgame.modal('startBreak', {
          class: 'arknova_popin',
          closeIcon: null,
          contentsTpl: this.formatIcon('break'),
          scale: 0.9,
          breakpoint: 700,
          autoShow: true,
        });
        await this.wait(1500);
        dialog.destroy();
        await this.wait(600);
      },

      onEnteringStateBreakDiscard(args) {
        if (!args._private) return;
        this.openHand();

        // Already made a selection => allow to cancel it
        if (args._private.selection != null) {
          if (args._private.selection.length > 0) {
            this.addSecondaryActionButton('cancelSelection', _('Cancel'), () =>
              this.takeAction('actCancelBreakDiscardSelection', {}, false)
            );
            args._private.selection.forEach((cardId) => {
              $(`card-${cardId}`).classList.add('selectedToDiscard');
            });
          }
        }
        // No selection yet => let the user click on it
        else {
          this.gamedatas.gamestate.args.n = args._private.n;
          this.gamedatas.gamestate.descriptionmyturn = _('<BREAK> Break: ${you} must discard ${n} card(s)');
          this.updatePageTitle();

          let cardIds = args._private.cards;
          this.onSelectNCards(cardIds, {
            n: args._private.n,
            class: 'selectedToDiscard',
            callback: (selectedElements) => {
              this.takeAction('actBreakDiscardSelectCards', { cardIds: JSON.stringify(selectedElements) });
            },
          });
        }
      },

      notif_updateBreakDiscardSelection(n) {
        this.clearPossible();
        this.updatePageTitle();
        this.onEnteringStateBreakDiscard(n.args.args);
      },

      async notif_finishBreak(n) {
        this.gamedatas.break = 0;
        this.updateBreakCounter();
        this.wait(1000);
      },

      ///////////////////////////////////////////////////////////
      //  ____                     _                         _
      // / ___|  ___ ___  _ __ ___| |__   ___   __ _ _ __ __| |
      // \___ \ / __/ _ \| '__/ _ \ '_ \ / _ \ / _` | '__/ _` |
      //  ___) | (_| (_) | | |  __/ |_) | (_) | (_| | | | (_| |
      // |____/ \___\___/|_|  \___|_.__/ \___/ \__,_|_|  \__,_|
      ///////////////////////////////////////////////////////////

      setupScoreBoard() {
        let grid = '';
        let duplicatedGrid = '';
        const rows = [114, 84, 54, 24, -6];
        const cRows = [0, 14, 24, 34, 42];
        for (let i = 0; i < rows.length - 1; i++) {
          for (let j = rows[i]; j > rows[i + 1]; j--) {
            grid += `<div class='appeal-slot' id='appeal-${j}'></div>`;
          }
          for (let j = cRows[i]; j < cRows[i + 1]; j++) {
            let step = j == 0 ? 1 : j <= 10 ? 2 : 3;
            let wide = step == 3 ? 'wide' : '';
            grid += `<div class='conservation-slot ${wide}' id='conservation-${j}' style="grid-column-end: span ${step}"></div>`;

            if (j <= 10) {
              duplicatedGrid += `<div class='conservation-slot' id='conservation-duplicate-${j}'></div>`;
            }
          }
        }
        duplicatedGrid += `<div id='conservation-duplicate-off'></div>`;
        $('conservation-track-duplicate').insertAdjacentHTML('beforeend', duplicatedGrid);

        this._scoreboardModal = new customgame.modal('showScoreboard', {
          class: 'arknova_popin',
          closeIcon: 'fa-times',
          closeAction: 'hide',
          verticalAlign: 'flex-start',
          contentsTpl: `<div id='arknova-scoreboard'>${grid}</div>`,
          scale: 0.95,
          breakpoint: 1400,
        });
        this.updateScoreboardBonuses();

        $('open-scoreboard').addEventListener('click', () => this._scoreboardModal.show());
        $('conservation-track-duplicate').addEventListener('click', () => this._scoreboardModal.show());
      },

      updateScoreboardBonuses() {
        ['arknova-scoreboard', 'conservation-track-duplicate', 'max-reputation-bonus-holder'].forEach((container) => {
          // Remove potential existing ones
          [...$(container).querySelectorAll('.scoreboard-bonus')].forEach((elt) => {
            this.destroy(elt);
          });

          // Create bonuses according to gamedatas
          let cBonuses = this.gamedatas.conservationBonuses;
          Object.keys(cBonuses).forEach((conservation) => {
            Object.keys(cBonuses[conservation]).forEach((i) => {
              // MW: end of appeal track bonus
              if (conservation == 99) {
                if (container == 'conservation-track-duplicate') return;
                else container = 'max-reputation-bonus-holder';
              }

              let b = cBonuses[conservation][i];
              let id =
                (container == 'conservation-track-duplicate' ? 'duplicate-' : '') + `scoreboard-bonus-${conservation}-${i}`;
              if (!$(id)) {
                $(container).insertAdjacentHTML(
                  'beforeend',
                  `<div class='scoreboard-bonus ${b.permanent ? 'permanent' : ''}' id='${id}'>${this.formatBonus(b.bonus)}</div>`
                );
              }
            });
          });
        });
      },

      async notif_takeBonus(n) {
        debug('Notif: taking a bonus', n);

        if (n.args.remove) {
          let t = n.args.remove.split('-');
          let meeple = $(`scoreboard-bonus-${t[0]}-${t[1]}`).querySelector('.arknova-bonus-container');
          let target = `player_board_${n.args.player_id}`;
          if (n.args.meeple && n.args.player_id == this.player_id) {
            target = $(`kept-bonus-container-${n.args.player_id}`);
          }

          await this.slide(meeple, target, { destroy: true, phantom: false });

          if (n.args.meeple) {
            this.addMeeple(n.args.meeple);
          }

          this.gamedatas.conservationBonuses = n.args.conservationBonuses;
          this.updateScoreboardBonuses();
        } else {
          await this.wait(1200);
        }
      },

      displayEngine(engine) {
        let html = '<ul>';
        engine.childs.forEach((node) => (html += this.convertNodeToHtml(node)));
        html += '</ul>';
        $('engine-display').innerHTML = html;
      },

      convertNodeToHtml(node) {
        let title = node.action ? node.action : node.type;
        let html = `<li><div>${title}</div>`;
        if (node.childs && node.childs.length) {
          html += '<ul>';
          node.childs.forEach((child) => (html += this.convertNodeToHtml(child)));
          html += '</ul>';
        }
        html += '</li>';
        return html;
      },

      notif_finalScoring(n) {
        debug('Notif: final scoring');
        // Update score
        this._scoreCounters[n.args.player_id].toValue(n.args.score);

        // Display scoring card
        n.args.scoringHand.forEach((card) => {
          if (!$(`card-${card.id}`)) this.addZooCard(card);
        });

        this.wait(1000);
      },

      /////////////////////////////
      //  _____
      // |_   _|__  _   _ _ __
      //   | |/ _ \| | | | '__|
      //   | | (_) | |_| | |
      //   |_|\___/ \__,_|_|
      /////////////////////////////

      /*
       * Display an helper tour
       */
      setupTour() {
        this._tourModal = new customgame.modal('showTour', {
          class: 'arknova_popin',
          closeIcon: 'fa-times',
          openAnimation: true,
          openAnimationTarget: 'open-tour',
          title: _('Ark Nova Tour'),
          contents: this.tplTourContent(),
          closeAction: 'hide',
          verticalAlign: 'flex-start',
        });

        dojo.connect($('open-tour'), 'onclick', () => this.showTour());
        this.addTooltip('open-tour', '', _('Show help tour.'));

        dojo.query('#tour-slider-container .tour-link').forEach((elt) => {
          let href = elt.getAttribute('href');
          dojo.connect(elt, 'click', () => this.setTourSlide(href));
        });

        dojo.connect($('neverShowMe'), 'change', function () {
          localStorage.setItem('arknovaTour', this.checked ? 1 : 0);
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
          "Welcome to Ark Nova on BGA. This is a brief tour of the interface, to make sure you'll enjoy your games to the fullest. Note that this is not a substitute for knowing the rules, but we will help you to see how the game is implemented here on BGA."
        );
        let introSectionUI = _('Global interface overview');
        let introSectionMajorChanges = _('Major changes compared to printed version');
        let introSectionBugs = _('Report a bug');

        let panelInfoBubble = _("Let's start with this panel next to your name: a very handy toolbox.");
        let panelInfoItems = [
          _(
            "Top part: progress information depending on the mode you are playing on. In a multiplayer game, you'll see the progress towards a break, and in the solo game you'll see the round tracker instead."
          ),
          _('My face: open this tour if you need it later'),
          _(
            "Switch: toggle the safe/help mode: when enabled, clicking will not do anything in the game, but instead will open tooltips on any elements with a question mark on it, making it sure you won't misclick"
          ),
          _(
            "Settings: open the settings menu, allowing you lots of ways to customize your experience to your needs.  Take some time to play with them until you're comfortable."
          ),
          _(
            'Scoreboard: open the scoreboard display, to show the appeal, income, and conservation points of each player, as well as the bonus tiles available on the CP track.'
          ),
          _(
            'In a multiplayer game, the x-token icon is here to remind you that, once the end of break track is reached (through players taking cards, using sponsors for cash, or the Jumping animal ability), the player who caused the break will get an x-token.'
          ),
          _(
            'Clicking on the top part will display a reminder of what is happening during a break: check hand size, reclaim association workers, remove any tokens on action cards, gain income and replenish the card display and the association board.'
          ),
        ];

        let playerPanelBubble = _('Below that are the player panels. They also contain lots of useful information.');
        let playerPanelItems = [
          _('Next to your name: click on the eye icon to focus on the corresponding zoo board.'),
          _('Current score: BGA is computing score following the publisher change on that topic (offset of 100)'),
          _('Current money balance'),
          _('Current x-token balance'),
          _('Current reputation level'),
          _('Current conservation point level'),
          _('Current appeal level, and how much income you get each break'),
          _('Number of cards in hand, with a smaller marker above to say how many end game scoring cards the player is holding.'),
          _(
            "Below these, you can see the current order of action cards for the player, from strength 1 to strength 5, as well as which ones have been upgraded (in pink).  Hovering over these icons will show you both sides of the action card, so you can remind yourself what the actions can and can't do."
          ),
          _(
            'You can also see which card icons they have in play, so you can keep track of how close they are to fulfilling the conservation goals of the game. '
          ),
        ];
        let playerPanelRemark = _(
          'You can actually click anywhere on the player panel to make the interface focus on the corresponding zoo board, not just on the eye icon.'
        );

        let zooMapBubble = _(
          'Then we have the map. In the notepad you will find the name of the map owner and arrows for quickly switching to other maps. Also, the notepad has the name of the zoo map, and hovering over the name will tell you the special ability of the map.'
        );
        let zooMapItems = [
          _('Above the zoo map is a small strip showing the quantity of each icon the player has, as in the player panel.'),
          _(
            'On the left of your map are the possible bonuses for supporting a conservation project. You can hover over each bonus to see what it does.'
          ),
          _('The center of your map shows locations for you to put enclosures and other structures.'),
          _(
            'To the right of your zoo map are spaces for partner zoos and partner universities. Gaining some of them will grant some bonus.'
          ),
        ];
        let zooMapRemark = _(
          'You can click on the shovel on the top right of your map to display a helper with all the possible enclosures available when using the Build action!'
        );
        let actionCardBubble = _(
          'Below your zoo map is a larger representation of the players action cards. You can hover on any of them to also see both sides of the action card.'
        );

        let tableauBubble = _(
          'Next to the map, we have the cards played by the player: animals on the left, sponsors on the right. Depending on your setting for hand location, you might also see your hand of cards here.'
        );
        let cardBtnBubble = _(
          'By default on desktop, hand of cards can be accessed instead by clicking these buttons on the very bottom left of your screen. Click the left one to see animals/sponsors, and the right one to see your final scoring card(s).'
        );

        let displayBubble = _(
          'In this area you can see the reputation track and the card row. Each player starts with 1 reputation, and gaining more will allow them to upgrade an action card, gain a new worker, and possibly get other benefits. Many effects in the game also let you take cards from reputation range, which is any card above or to the left of your reputation marker.'
        );
        let displayRemark = _(
          'Note that your reputation cannot exceed 9 until you have upgraded your Cards action (note the pink background).'
        );
        let displayRemark2 = _(
          'Some upgraded action cards let you play cards directly from the card row (within reputation range) - there is an additional cost for this, shown on the top right of each card (1 for the leftmost card, 2 for the next one, etc.).'
        );

        let associationBubble = _(
          'And finally, the association board. At the top, the first ten spot of the conservation track are displayed to make it easier to follow the bonuses left and progress of all players. Click anywhere on that board to display the full scoreboard.'
        );
        let associationItems = [
          _(
            'On the left of the board, you can see how many workers in reserve each player currently has, a very important information so that you know what others might do during their turn.'
          ),
          _(
            'Below the Association board are the base conservation projects for the game - showing how many of which icons you need to obtain Conservation Points.  Remember you can only support each project once.'
          ),
          _(
            'Above the Association board are spaces for other conservation projects.  These can be pushed off the board if enough projects are played by players throughout the game'
          ),
        ];

        let majorChangesBubble = _(
          "BGA's implementation implements the 2 rules update from Ark Nova publisher, that were announced publicly in this thread:"
        );
        let majorChangesItems = [
          _(
            'Scoring: all conservation point spaces feature a small number so that to determine your score, you only have to add the number of the space with your conservation point marker on it and your appeal to have your total score. No more calculating the distance between your 2 markers. If your markers cross, your score will be above 100, if they dont, it will be below. Both scores are displayed in this implementation, the new value (starting at -14) and the old value (starting at -114 and going up to 0 to end the game).'
          ),
          _(
            'Solo play and Sponsor cards with the requirement "maximum of 25 appeal": If you start a solo game with 10 or 20 starting appeal, these requirements should read as 35 or 45 appeal.'
          ),
        ];

        let bugBubble = _(
          "No code is error-free. But we have around 40% of FALSE BUG REPORTS, so before reporting a bug, please (double) check the rules first to see if it's really a bug or not. Then follow the following steps."
        );
        let bugItems = [
          _('If the issue is related to a card, please give the card name AND number.'),
          _(
            'If your language is not English, please check the English card description. If there is an incorrect translation to your language, please do not report a bug and use the translation module (Community > Translation) to fix it directly.'
          ),
          _(
            'When you encounter a bug, please refresh your page to see if the bug goes away. Knowing whether or not a bug is persisting through refresh or not will help us find and fix it, so please include that in the bug report!'
          ),
          _(
            'Always include the MOVE NUMBER in your report. This information can be found at the very top left part of your screen : Move #X'
          ),
          _('Try to include a screenshot as much as possible using imgur or other uploading services'),
        ];
        let bugReport = _('Report a new bug');

        let neverShowMe = _('Never show me this tour again');

        var bugUrl = this.metasiteurl + '/bug?id=0&table=' + this.table_id;

        return `
            <div id="tour-slider-container">
              <div id="tour-slide-intro" class="slide">
                <div class="bubble">${introBubble}</div>
                  <button href="panelInfo" class="action-button bgabutton bgabutton_blue tour-link">${introSectionUI}</button>
                  <button href="majorChanges" class="action-button bgabutton bgabutton_red tour-link">${introSectionMajorChanges}</button>
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
                <div class="tour-remark">
                  ${panelInfoItems[5]}<br />
                  ${panelInfoItems[6]}
                </div>
                ${nextBtn('panel')}
              </div>

              <div id="tour-slide-panel" class="slide">
                <div class="bubble">${playerPanelBubble}</div>
                <div class="split-hor">
                  <div>
                    <div class="tour-img" id="img-player-panel"></div>
                  </div>
                  <div>
                    <ul>
                      <li>${playerPanelItems[0]}</li>
                      <li>${playerPanelItems[1]}</li>
                      <li>${playerPanelItems[2]}</li>
                      <li>${playerPanelItems[3]}</li>
                      <li>${playerPanelItems[4]}</li>
                      <li>${playerPanelItems[5]}</li>
                      <li>${playerPanelItems[6]}</li>
                      <li>${playerPanelItems[7]}</li>
                    </ul>
                  </div>
                </div>
                <div class="bubble">
                  ${playerPanelItems[8]}<br />
                  ${playerPanelItems[9]}
                </div>
                <div class="tour-remark">${playerPanelRemark}</div>

                ${nextBtn('zooMap')}
              </div>


              <div id="tour-slide-zooMap" class="slide">
                <div class="bubble">${zooMapBubble}</div>
                <div class="split-hor">
                  <div>
                    <div class="tour-img" id="img-zooMap"></div>
                  </div>
                  <div>
                    <ul>
                      <li>${zooMapItems[0]}</li>
                      <li>${zooMapItems[1]}</li>
                      <li>${zooMapItems[2]}</li>
                      <li>${zooMapItems[3]}</li>
                    </ul>
                  </div>
                </div>
                <div class="tour-remark">${zooMapRemark}</div>
                <div class="bubble">${actionCardBubble}</div>                

                ${nextBtn('tableau')}
              </div>

              <div id="tour-slide-tableau" class="slide">
                <div class="bubble">${tableauBubble}</div>
                <div class="tour-img" id="img-tableau"></div>

                <div class="bubble">${cardBtnBubble}</div>
                <div class="tour-img" id="img-hand-btn"></div>

                ${nextBtn('display')}
              </div>



              <div id="tour-slide-display" class="slide">
                <div class="bubble">${displayBubble}</div>
                <div class="tour-img" id="img-display"></div>

                <div class="tour-remark">${displayRemark}</div>
                <div class="tour-remark">${displayRemark2}</div>

                ${nextBtn('association')}
              </div>


              <div id="tour-slide-association" class="slide">
                <div class="bubble">${associationBubble}</div>

                <div class="split-hor">
                  <div>
                    <div class="tour-img" id="img-association"></div>
                  </div>
                  <div>
                    <ul>
                      <li>${associationItems[0]}</li>
                      <li>${associationItems[1]}</li>
                      <li>${associationItems[2]}</li>
                    </ul>
                  </div>
                </div>

                ${nextBtn('majorChanges', _('Major changes'))}
              </div>


              <div id="tour-slide-majorChanges" class="slide">
                <div class="bubble">
                  ${majorChangesBubble}
                  <a href="https://boardgamegeek.com/thread/3057107/2-rules-updates-ark-nova">${_('BGG Thread')}</a>
                </div>

                <ul>
                  <li>${majorChangesItems[0]}</li>
                  <li>${majorChangesItems[1]}</li>
                </ul>

                ${nextBtn('intro', _('Back'))}
              </div>


              <div id="tour-slide-bugs" class="slide">
                <div class="bubble">${bugBubble}</div>

                <ul>
                  <li>${bugItems[0]}</li>
                  <li>${bugItems[1]}</li>
                  <li>${bugItems[2]}</li>
                  <li>${bugItems[3]}</li>
                  <li>${bugItems[4]}</li>
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
    }
  );
});
