define(['dojo', 'dojo/_base/declare'], (dojo, declare) => {
  const PLAYER_COUNTERS = ['appeal', 'reputation', 'conservation', 'money', 'handCount', 'scoringHandCount', 'xtoken', 'income'];
  const RESOURCES = [];
  const ALL_PLAYER_COUNTERS = PLAYER_COUNTERS.concat(RESOURCES);
  const COUNTER_MEEPLES = ['reputation', 'conservation', 'appeal'];
  let ICONS_SUMMARY = [
    ['Africa', 'Europe', 'Asia', 'Americas', 'Australia'],
    ['Bird', 'Predator', 'Herbivore', 'Reptile', 'Primate'],
    ['Bear', 'Pet', 'Science', 'Rock', 'Water'],
  ];

  let ANIMALS_SIZES = ['small', 'medium', 'large'];

  return declare('arknova.players', null, {
    getPlayers() {
      return Object.values(this.gamedatas.players);
    },

    isSolo() {
      return this.getPlayers().length == 1;
    },

    specialSetupPlayer(player) {
      // MAP 10
      if (player.mapId && player.mapId == 10) {
        $(`inPlay-animals-${player.id}`).insertAdjacentHTML(
          'beforeend',
          `<div class='rescueStation-holder' id='rescueStation-${player.id}-0'></div>
           <div class='rescueStation-holder' id='rescueStation-${player.id}-1'></div>
           <div class='rescueStation-holder' id='rescueStation-${player.id}-2'></div>`
        );
      }

      // MAP 11
      if (player.id == this.player_id && player.mapId && player.mapId == 11) {
        $('floating-hand-button-container').insertAdjacentHTML(
          'beforeend',
          `<div id="floating-stored-hand-button">
            <div class="arknova-icon icon-stored"><i class="fa fa-times" aria-hidden="true"></i></div>
          </div>`
        );
        $('floating-stored-hand-button').addEventListener('click', () => {
          let handWrapper = $('floating-hand-wrapper');
          if (handWrapper.dataset.open && handWrapper.dataset.open == 'storedHand') {
            delete handWrapper.dataset.open;
          } else {
            handWrapper.dataset.open = 'storedHand';
          }
        });
        $(`hand-${player.id}`).insertAdjacentHTML(
          'afterend',
          `<div id='stored-hand-${player.id}' class='player-board-stored-hand'>
              ${this.formatIcon('stored')}
            </div>`
        );
      }
    },

    setupPlayers() {
      if (this.isMarine()) {
        ICONS_SUMMARY[1].push('SeaAnimal');
      }

      // Change No so that it fits the current player order view
      let currentNo = this.getPlayers().reduce((carry, player) => (player.id == this.player_id ? player.no : carry), 0);
      let nPlayers = Object.keys(this.gamedatas.players).length;
      this.forEachPlayer((player) => (player.order = (player.no + nPlayers - currentNo) % nPlayers));
      this.orderedPlayers = Object.values(this.gamedatas.players).sort((a, b) => a.order - b.order);

      // Add player board and player panel
      this.orderedPlayers.forEach((player, i) => {
        this.place('tplPlayerBoard', player, 'arknova-main-container');
        if (player.mapId) {
          this.setupChangeBoardArrows(player.id);
        }
        player.scoringHand.forEach((card) => {
          this.addZooCard(card);
        });

        this.specialSetupPlayer(player);

        // MAP 11
        if (player.stored && player.id == this.player_id) {
          player.stored.forEach((card) => this.addZooCard(card));
        }

        // Score counters
        $(`player_score_${player.id}`).insertAdjacentHTML('beforebegin', `<span id="player_new_score_${player.id}"></span>`);

        // Panels
        this.place('tplPlayerPanel', player, `overall_player_board_${player.id}`);
        $(`overall_player_board_${player.id}`).addEventListener('click', () => this.goToPlayerBoard(player.id));
        $(`player_name_${player.id}`).insertAdjacentHTML(
          'beforeend',
          '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 576 512"><!--! Font Awesome Pro 6.2.1 by @fontawesome - https://fontawesome.com License - https://fontawesome.com/license (Commercial License) Copyright 2022 Fonticons, Inc. --><path d="M288 32c-80.8 0-145.5 36.8-192.6 80.6C48.6 156 17.3 208 2.5 243.7c-3.3 7.9-3.3 16.7 0 24.6C17.3 304 48.6 356 95.4 399.4C142.5 443.2 207.2 480 288 480s145.5-36.8 192.6-80.6c46.8-43.5 78.1-95.4 93-131.1c3.3-7.9 3.3-16.7 0-24.6c-14.9-35.7-46.2-87.7-93-131.1C433.5 68.8 368.8 32 288 32zM432 256c0 79.5-64.5 144-144 144s-144-64.5-144-144s64.5-144 144-144s144 64.5 144 144zM288 192c0 35.3-28.7 64-64 64c-11.5 0-22.3-3-31.6-8.4c-.2 2.8-.4 5.5-.4 8.4c0 53 43 96 96 96s96-43 96-96s-43-96-96-96c-2.8 0-5.6 .1-8.4 .4c5.3 9.3 8.4 20.1 8.4 31.6z"/></svg>'
        );
        $('workers-reserves').insertAdjacentHTML('beforeend', `<div id='reserve-${player.id}' class='player-reserve'></div>`);

        if (player.id == this.player_id) {
          $(`player-board-${this.player_id}`).addEventListener('mouseover', (evt) => {
            if (evt.target.classList.contains('zoo-map-cell') && this.onHoverCell) {
              this.onHoverCell(evt.target);
            }
          });
          $(`player-board-${this.player_id}`).addEventListener('click', (evt) => {
            if (this.onClickCell) {
              // Go up in the DOM tree until a zoo-map-cell is found
              let elt = evt.target;
              while (!elt.classList.contains('zoo-map-cell') && elt.id != `player-board-${this.player_id}`) {
                elt = elt.parentNode;
              }

              if (elt.classList.contains('zoo-map-cell')) {
                this.onClickCell(elt);
              }
            }
          });
        }

        // if (i == 0) {
        //   $('arknova-main-container').insertAdjacentHTML('beforeend', '<div id="engine-display"></div>');
        // }
      });
      this.setupPlayersCounters();
      this.updateActionCards();
      this.activateShowBuildingHelperButtons();
      this.updatePlayersMapStatuses();
      this.updatePlayersHandStatuses();
      this.updateConservationProjectStrength();
    },

    activateShowBuildingHelperButtons() {
      [...document.querySelectorAll('.buildings-helper-toggle')].forEach((elt) => {
        if (elt.id && this.tooltips[elt.id]) return;

        elt.addEventListener('click', function () {
          let isOpen = this.parentNode.classList.contains('open');
          this.parentNode.classList.toggle('open');
          if (isOpen) this.parentNode.classList.add('closedAnim');
        });
      });

      this.addTooltipToClass('buildings-helper-toggle', '', _('Click to show the list of possible enclosures'));
    },

    onChangeHandLocationSetting(v) {
      let hand = $(`hand-${this.player_id}`);
      let scoringHand = $(`scoring-hand-${this.player_id}`);
      let storedHand = $(`stored-hand-${this.player_id}`);

      if (hand) {
        let container = this.isFloatingHand() ? 'floating-hand' : `player-board-cards-${this.player_id}`;
        $(container).insertAdjacentElement('beforeend', hand);
        $(container).insertAdjacentElement('beforeend', scoringHand);
        if (storedHand) $(container).insertAdjacentElement('beforeend', storedHand);
        $('floating-hand-wrapper').classList.toggle('active', this.isFloatingHand());
        hand.style.order = v == 1 ? 1 : 4;

        if (v == 3) {
          this.openHand();
        }
      }

      this.ensureNoSortableHandOnTouchDevice();
    },

    updateHandCards() {
      if (this.isSpectator) return;
      this.empty(`hand-${this.player_id}`);
      let hand = this.gamedatas.players[this.player_id].hand;
      hand.forEach((card) => {
        if ($(`card-${card.id}`)) {
          $(`hand-${this.player_id}`).insertAdjacentElement('beforeend', $(`card-${card.id}`));
        } else {
          this.addZooCard(card);
        }
      });

      this.empty(`scoring-hand-${this.player_id}`);
      let scoringHand = this.gamedatas.players[this.player_id].scoringHand;
      scoringHand.forEach((card) => {
        if ($(`card-${card.id}`)) {
          $(`scoring-hand-${this.player_id}`).insertAdjacentElement('beforeend', $(`card-${card.id}`));
        } else {
          this.addZooCard(card);
        }
      });

      if ($(`stored-hand-${this.player_id}`)) {
        //this.empty(`stored-hand-${this.player_id}`);
        let storedHand = this.gamedatas.players[this.player_id].stored;
        storedHand.forEach((card) => {
          if ($(`card-${card.id}`)) {
            $(`stored-hand-${this.player_id}`).insertAdjacentElement('beforeend', $(`card-${card.id}`));
          } else {
            this.addZooCard(card);
          }
        });
      }
    },

    onChangePlayerBoardsLayoutSetting(v) {
      if (v == 0) {
        this.goToPlayerBoard(this.orderedPlayers[0].id);
      } else {
        this._focusedPlayer = null;
      }
    },

    goToPlayerBoard(pId, evt = null) {
      if (evt) evt.stopPropagation();

      let v = this.settings.playerBoardsLayout;
      if (v == 0) {
        // Tabbed view
        this._focusedPlayer = pId;
        [...$('arknova-main-container').querySelectorAll('.ark-player-board-resizable')].forEach((board) =>
          board.classList.toggle('active', board.id == `player-board-resizable-${pId}`)
        );
        [...$('arknova-main-container').querySelectorAll('.player-board-cards')].forEach((board) =>
          board.classList.toggle('active', board.id == `player-board-cards-${pId}`)
        );
        [...$('arknova-main-container').querySelectorAll('.player-board-action-cards-resizable')].forEach((board) =>
          board.classList.toggle('active', board.id == `action-cards-${pId}`)
        );
      } else if (v == 1) {
        // Multiple view
        this._focusedPlayer = null;
        window.scrollTo(0, $(`player-board-${pId}`).getBoundingClientRect()['top'] - 30);
      }
    },

    setupChangeBoardArrows(pId) {
      let leftArrow = $(`player-board-${pId}`).querySelector('.prev-player-board');
      if (leftArrow) leftArrow.addEventListener('click', () => this.switchPlayerBoard(-1));

      let rightArrow = $(`player-board-${pId}`).querySelector('.next-player-board');
      if (rightArrow) rightArrow.addEventListener('click', () => this.switchPlayerBoard(1));
    },

    getDeltaPlayer(pId, delta) {
      let playerOrder = this.orderedPlayers;
      let index = playerOrder.findIndex((elem) => elem.id == pId);
      if (index == -1) return -1;

      let n = playerOrder.length;
      return playerOrder[(((index + delta) % n) + n) % n].id;
    },

    switchPlayerBoard(delta) {
      let pId = this.getDeltaPlayer(this._focusedPlayer, delta);
      if (pId == -1) return;
      $(`player-board-${this._focusedPlayer}`).querySelector('.buildings-helper').classList.remove('open', 'closedAnim');
      this.goToPlayerBoard(pId);
    },

    tplZooMap(map, player = null) {
      let pId = player == null ? 0 : player.id;

      // Create cells
      let zooBoard = `<div class='zoo-board'>`;
      let dim = { x: 9, y: 7 };
      for (let x = 0; x < dim.x; x++) {
        let size = dim.y - (x % 2 == 0 ? 1 : 0);
        for (let y = 0; y < size; y++) {
          let row = 2 * y + (x % 2 == 0 ? 1 : 0);
          let style = `grid-row: ${row + 1} / span 2; grid-column: ${3 * x + 1} / span 4`;

          let uid = x + '_' + row;
          let className = '';
          let content = '';
          if (map.terrains.Rock.includes(uid)) {
            className += ' rock';
          }
          if (map.terrains.Water.includes(uid)) {
            className += ' water';
          }
          if (map.upgradeNeeded.includes(uid)) {
            className += ' upgradeNeeded';
            content = "<div class='upgradeNeeded-marker'></div>";
          }
          if (map.bonuses[uid]) {
            className += ' bonus';
            content += this.formatBonus(map.bonuses[uid]);
          }
          zooBoard += `<div class='zoo-map-cell${className}' style='${style}' data-x='${x}' data-y='${row}'>${content}</div>`;
          // zooBoard += `<div class='zoo-map-cell${className}' style='${style}' data-x='${x}' data-y='${row}'>${x}_${row}</div>`;
        }
      }
      // Geographical Zoo
      if (map.id == 9) {
        ['africa', 'europe', 'asia', 'americas', 'australia'].forEach((continent) => {
          zooBoard += `<div class='continent-area' data-continent='${continent}'>
            <div class='continent-area-slot'></div>
            <div class='continent-icon'>${this.formatIcon(continent)}</div>
          </div>`;
        });
      }
      // Drawing Board
      if (map.id == 13) {
        const bonuses = [
          { type: 'money', n: 3 },
          { type: 'appeal', n: 2 },
          { type: 'reputation', n: 1 },
          { type: 'hunter', n: 4 },
        ];
        bonuses.forEach((bonus, i) => {
          zooBoard += `<div class='arknova-icon icon-map-13-slot' data-quarter='${i}'>
            <div class='quarter-area-slot'></div>
            <div class='quarter-icon'>${this.formatIcon(bonus.type, bonus.n)}</div>
          </div>`;
        });
      }
      zooBoard += '</div>';

      // Bonus spaces
      let bonusSpacesIncome = '';
      let bonusSpaceImmediate = '';
      map.bonusSpaces.forEach((space, i) => {
        let secondBonus = '';
        if (map.id == 'T1' && i == 0) {
          secondBonus = this.formatIcon('hand-cards');
        }

        let tpl = `<div class='bonus-space'>
          <div class='cube-holder' id='bonus-${pId}-${i}'></div>
          ${this.formatBonus(space.bonus, space.type)}
          ${secondBonus}
        </div>`;

        if (space.type == 'income') bonusSpacesIncome += tpl;
        else bonusSpaceImmediate += tpl;
      });
      let texts = [
        _('You will gain the bonus immediately when you remove the corresponding cube, and then again during each break.'),
        _('Removing cube from these spaces is done by supporting conservation projects (strength 5 association action).'),
        _('You will gain the bonus immediately when you remove the corresponding cube.'),
        _('Gain 7 appeal if your zoo map is completely covered.'),
      ];

      // Partner zoos
      let partnerZoos = '';
      for (let i = 4; i > 0; i--) {
        let bonus = map.partnerZooBonuses[i] ? this.formatBonus(map.partnerZooBonuses[i]) : '';
        partnerZoos += `<div class='partner-zoo-space'>
          ${bonus}
          <div class='partner-zoo-holder' id='partner-${pId}-${i}'></div>
        </div>`;
      }

      // Universities
      let universities = '';
      for (let i = 3; i > 0; i--) {
        let bonus = map.facBonuses[i] ? this.formatBonus(map.facBonuses[i]) : '';
        universities += `<div class='fac-space'>
          ${bonus}
          <div class='fac-holder' id='university-${pId}-${i}'></div>
        </div>`;
      }

      // Workers
      let workers = '';
      for (let i = 1; i <= 3; i++) {
        let bonuses = map.workersBonuses[i] ? map.workersBonuses[i] : {};
        let bonus = Object.keys(bonuses)
          .map((type) => {
            let b = {};
            b[type] = bonuses[type];
            return this.formatBonus(b);
          })
          .join('');
        workers += `<div class='worker-space'>
          <div class='arknova-icon icon-bordered-worker icon-background'></div>
          <div class='space-counter'>${i}</div>
          ${bonus}
          <div class='worker-holder' id='worker-${pId}-${i}'></div>
        </div>`;
      }

      // Prev/next arrows
      let arrows = '';
      if (player != null && Object.keys(this.gamedatas.players).length > 1) {
        arrows += "<div class='prev-player-board'>&lt;</div><div class='next-player-board'>&gt;</div>";
      }

      // Basic player infos
      let playerInfos =
        player == null
          ? `${_('Map')} ${map.id}`
          : `<div class='player-board-name' style='color:#${player.color}'>${player.name}</div>`;

      // Worker counter
      let workerCounter =
        player == null
          ? ''
          : `<div class='worker-counter-container' id='worker-counter-container-${player.id}'>
        <span id='counter-${player.id}-worker'></span>
        <div class="arknova-meeple arknova-icon icon-worker" data-type="worker" data-state="0" data-color="${player.color}"></div>
      </div>`;

      // Map icon
      let mapDesc = '';
      if (map.id != 'A' && map.id != 0) {
        let iconsMap = {
          1: 'tower',
          '1a': 'tower',
          2: 'gates',
          '2a': 'gates',
          3: 'lake',
          '3a': 'lake',
          4: 'harbor',
          '4a': 'harbor',
          5: 'restaurant',
          '5a': 'restaurant',
          6: 'institute',
          '6a': 'institute',
          7: 'icecream',
          '7a': 'icecream',
          8: 'hollywood',
          '8a': 'hollywood',
          9: '',
          10: '',
          11: '',
          12: '',
          13: '',
          14: '',
          T1: '',
        };
        let iconMap = iconsMap[map.id] == '' ? '' : this.formatIcon(iconsMap[map.id]);
        mapDesc = `<div class='map-desc'>
          <div>${_(map.name)}</div>
          <div>${iconMap}</div>
        </div>`;
      }

      let fullMapBonus = '';
      if (map.id == 13) {
        fullMapBonus = `<div class='full-map-bonus no-bonus' id="${this.registerCustomTooltip(_('No bonus'))}">
          ${this.formatBonus({ appeal: 0 }, 'noBonus', false)}
          </div>`;
      } else {
        fullMapBonus = `<div class='full-map-bonus no-bonus' id="${this.registerCustomTooltip(texts[3])}">
          ${this.formatBonus({ appeal: 7 }, 'bonusTile', false)}
        </div>`;
      }

      let linkedBonuses = '';
      Object.entries(map.facPartnerZooLinkedBonuses).forEach(([slot, bonus]) => {
        linkedBonuses += `<div class='bonus-link link-${slot}'>${this.formatBonus(bonus)}</div>`;
      });

      return `<div class='zoo-map' id='zoo-map-${pId}'>
          <div class='map-infos'  id='${this.registerCustomTooltip(this.formatString(_(map.desc)))}'>
            <div class='player-infos'>
              ${playerInfos}
              ${workerCounter}
              ${mapDesc}
            </div>
            ${arrows}
            <div class='map-name'>
              ${_('Map')} ${map.id}
            </div>
          </div>
          <div class='zoo-map-bonus-spaces'>
            <div class='zoo-map-workers'>
              ${workers}
            </div>
            <div class='bonus-spaces-income ${player == null ? 'preview' : ''}'>
              <div class='arknova-icon icon-immediate-income' id="${this.registerCustomTooltip(texts[0])}"></div>
              ${bonusSpacesIncome}
            </div>
            <div class='arknova-icon icon-place-cube' id="${this.registerCustomTooltip(texts[1])}"></div>
            <div class='bonus-spaces-immediate ${player == null ? 'preview' : ''}'>
              <div class='arknova-icon icon-bordered-immediate' id="$${this.registerCustomTooltip(texts[2])}"></div>
              ${bonusSpaceImmediate}
            </div>
          </div>

          <div class='zoo-map-board'>
            <div class='zoo-map-board-border'>
              <div class='zoo-map-board-background' data-map='${map.asset}'>
              ${zooBoard}
              </div>
            </div>
            ${fullMapBonus}
            <div class='buildings-helper'><div class='buildings-helper-toggle'>${this.formatIcon('action-build')}</div></div>
          </div>

          <div class='zoo-map-association'>
            <div class='zoo-map-partner-zoos'>
              ${partnerZoos}
            </div>
            <div class='zoo-map-universities'>
              ${universities}
            </div>
            ${linkedBonuses}
          </div>
        </div>`;
    },

    tplPlayerBoard(player) {
      let iconSummary = this.tplIconsSummary(player, true);
      let zooMap = player.mapId ? this.tplZooMap(MAPS_DATA[player.mapId], player) : '';
      let slots = [1, 2, 3, 4, 5];
      if (player.mapId == 12) slots.push(6);

      return (
        `<div class='ark-player-board-resizable' id='player-board-resizable-${player.id}'>
          <div class='ark-player-board' id='player-board-${player.id}'>        
            ${iconSummary}
            ${zooMap}
          </div>
        </div>
        <div class="player-board-cards" id='player-board-cards-${player.id}'>
          <div class='player-board-inPlay-animals' id='inPlay-animals-${player.id}'>
            ${this.formatIcon('action-animals')}
            <div class='player-board-sizes-counters'>
              <span id="icons-${player.id}-small-animals">0</span>
              ${this.formatIcon('animal-size-2')}
              <span id="icons-${player.id}-medium-animals">0</span>
              ${this.formatIcon('animal-size-3')}
              <span id="icons-${player.id}-large-animals">0</span>
              ${this.formatIcon('animal-size-4')}
            </div>  
          </div>
          <div class='player-board-inPlay-sponsors' id='inPlay-sponsors-${player.id}'>${this.formatIcon('action-sponsors')}</div>
          ` +
        (player.id == this.player_id ? `<div class='player-board-hand' id='hand-${player.id}'></div>` : '') +
        `
          <div class='player-board-scoring-hand' id='scoring-hand-${player.id}'></div>
        </div>
        <div class='player-board-action-cards-resizable' id='action-cards-${player.id}'>
          <div class='player-board-action-cards'>
            ` +
        slots
          .map(
            (i) => `<div class='action-card-slot slot-${i}' id='action-card-slot-${player.id}-${i}'>
                <div class='action-card-slot-strength'>${i}</div>
              </div>`
          )
          .join('') +
        `
          </div>
        </div>`
      );
    },

    tplPlayerPanel(player) {
      let iconSummary = this.tplIconsSummary(player);
      return `<div class='player-info'>
        <div class="arknova-icon icon-money" id="counter-${player.id}-money"></div>

        <div class='xtoken-holder'>
          <div class="player-xtoken" id="counter-${player.id}-xtoken"></div>
          <div class="arknova-icon icon-xtoken"></div>
        </div>

        <div class="arknova-icon icon-reputation" id="counter-${player.id}-reputation"></div>

        <div class="arknova-icon icon-conservation" id="counter-${player.id}-conservation"></div>

        <div class="appeal-holder">
          <div class="arknova-icon icon-appeal" id="counter-${player.id}-appeal"></div>
          <div class="arknova-icon icon-money" id="counter-${player.id}-income"></div>
        </div>

        <div class='handCount-holder'>
          <div class="player-handCount" id="counter-${player.id}-handCount"></div>
          <div class="player-max-handCount" id="maxHandCount-${player.id}"></div>
          <div class="player-handCount-icon"></div>
          <div class="scoringHandCount-holder">
            + <span class="player-scoringHandCount" id="counter-${player.id}-scoringHandCount">0</span>
              <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 384 512"><!--! Font Awesome Pro 6.3.0 by @fontawesome - https://fontawesome.com License - https://fontawesome.com/license (Commercial License) Copyright 2023 Fonticons, Inc. --><path d="M32 0C14.3 0 0 14.3 0 32S14.3 64 32 64V75c0 42.4 16.9 83.1 46.9 113.1L146.7 256 78.9 323.9C48.9 353.9 32 394.6 32 437v11c-17.7 0-32 14.3-32 32s14.3 32 32 32H64 320h32c17.7 0 32-14.3 32-32s-14.3-32-32-32V437c0-42.4-16.9-83.1-46.9-113.1L237.3 256l67.9-67.9c30-30 46.9-70.7 46.9-113.1V64c17.7 0 32-14.3 32-32s-14.3-32-32-32H320 64 32zM96 75V64H288V75c0 25.5-10.1 49.9-28.1 67.9L192 210.7l-67.9-67.9C106.1 124.9 96 100.4 96 75z"/></svg>
          </div>
        </div>
      </div>
      <div class='action-cards-summary'>
        <div class='slot-1'></div>
        <div class='slot-2'></div>
        <div class='slot-3'></div>
        <div class='slot-4'></div>
        <div class='slot-5'></div>
      </div>
      ${iconSummary}
      <div class='kept-bonus-container' id='kept-bonus-container-${player.id}'></div>`;
    },

    updatePlayersMapStatuses() {
      this.forEachPlayer((player) => {
        let status = player.mapStatus;
        if (!status) return;

        // MAP 12
        if (player.mapId == 12) {
          $(`action-cards-${player.id}`).dataset.bonusStrength = status.bonusStrength;
          return;
        }
      });
    },

    updatePlayersHandStatuses() {
      this.forEachPlayer((player) => {
        let status = player.handStatus;

        $(`maxHandCount-${player.id}`).innerHTML = '/' + status[0];
        $(`maxHandCount-${player.id}`).parentNode.classList.toggle('tooMuch', status[1]);
      });
    },

    updateConservationProjectStrength() {
      $('veteranirian-other').classList.remove('active');
      $('veteranirian-myself').classList.remove('active');

      let found = false;
      this.forEachPlayer((player) => {
        let status = player.projectStrength;
        if (status == 4) {
          found = true;
          if (player.id == this.player_id) {
            $('veteranirian-myself').classList.add('active');
          }
        }
      });

      if (found) {
        $('veteranirian-other').classList.add('active');
      }
    },

    ////////////////////////////////////////////////////
    //   ____                  _
    //  / ___|___  _   _ _ __ | |_ ___ _ __ ___
    // | |   / _ \| | | | '_ \| __/ _ \ '__/ __|
    // | |__| (_) | |_| | | | | ||  __/ |  \__ \
    //  \____\___/ \__,_|_| |_|\__\___|_|  |___/
    //
    ////////////////////////////////////////////////////
    /**
     * Create all the counters for player panels
     */
    setupPlayersCounters() {
      this._playerCounters = {};
      this._playerCountersMeeples = {};
      this._scoreCounters = {};
      this.forEachPlayer((player) => {
        this._playerCounters[player.id] = {};
        this._playerCountersMeeples[player.id] = {};
        ALL_PLAYER_COUNTERS.forEach((res) => {
          let v = player[res];
          this._playerCounters[player.id][res] = this.createCounter(`counter-${player.id}-${res}`, v);

          if (COUNTER_MEEPLES.includes(res)) {
            this._playerCountersMeeples[player.id][res] = this.addMeeple({
              id: `${res}-${player.id}`,
              pId: player.id,
              type: 'cylinder',
              location: `${res}_${v}`,
            });
          }
        });
        this._scoreCounters[player.id] = this.createCounter('player_new_score_' + player.id, player.newScore);

        // DUPLICATED CYLINDER FOR CONSERVATION
        this._playerCountersMeeples[player.id]['conservation-duplicate'] = this.addMeeple({
          id: `conservation-duplicate-${player.id}`,
          pId: player.id,
          type: 'cylinder',
          location: `conservation-duplicate_${player.conservation}`,
        });

        // Worker counter
        if ($(`counter-${player.id}-worker`)) {
          this._playerCounters[player.id]['worker'] = this.createCounter(`counter-${player.id}-worker`, 0);
        }
      });
      this.updatePlayersCounters(false);
    },

    /**
     * Update all the counters in player panels according to gamedatas, useful for reloading
     */
    updatePlayersCounters(anim = true) {
      this.forEachPlayer((player) => {
        PLAYER_COUNTERS.forEach((res) => {
          let value = player[res];
          this._playerCounters[player.id][res].goTo(value, anim);

          // Slide meeples
          if (COUNTER_MEEPLES.includes(res)) {
            let meeple = this._playerCountersMeeples[player.id][res];
            let container = this.getMeepleContainer({ location: `${res}_${value}`, pId: player.id });
            if (meeple.parentNode != container) {
              if (anim) {
                this.slide(meeple, container);
              } else {
                dojo.place(meeple, container);
              }
            }

            // DUPLICATED CONSERVATION
            if (res == 'conservation') {
              meeple = this._playerCountersMeeples[player.id]['conservation-duplicate'];
              container = this.getMeepleContainer({ location: `conservation-duplicate_${value}`, pId: player.id });
              if (meeple.parentNode != container) {
                if (anim) {
                  this.slide(meeple, container);
                } else {
                  dojo.place(meeple, container);
                }
              }
            }
          }
        });
      });

      this.updateWorkerCounters(anim);
      this.updateDuplicateConservationBoard();
      this.updatePlayersIconsSummaries();
    },

    updateWorkerCounters(anim = true) {
      this.forEachPlayer((player) => {
        if (!this._playerCounters[player.id]['worker']) return;

        let pId = player.id;
        let workers = $(`reserve-${pId}`).querySelectorAll('.icon-worker').length;
        this._playerCounters[pId]['worker'].goTo(workers, anim);
      });
    },

    /**
     * Use this tpl for any counters that represent qty of meeples in "reserve", eg xtokens
     */
    tplResourceCounter(player, res, prefix = '') {
      return this.formatString(`
        <div class='player-resource resource-${res}'>
          <span id='${prefix}counter-${player.id}-${res}' 
            class='${prefix}resource-${res}'></span>${this.formatIcon(res)}
          <div class='reserve' id='${prefix}reserve-${player.id}-${res}'></div>
        </div>
      `);
    },

    /**
     * Animate a player counter receiving/loosing stuff
     */
    async animatePlayerCounter(pId, type, n) {
      let oldVal = +this._playerCounters[pId][type].getValue();
      let newVal = oldVal + n;
      if (type == 'reputation' && newVal > 15) newVal = 15;
      if (type == 'xtoken' && newVal > 5) newVal = 5;
      if (oldVal == newVal) {
        return Promise.resolve();
      }

      let meeple = null,
        container = null;
      if (COUNTER_MEEPLES.includes(type)) {
        meeple = this._playerCountersMeeples[pId][type];
        container = this.getMeepleContainer({
          pId,
          type,
          location: `${type}_${newVal}`,
        });
      }

      if (this.isFastMode()) {
        this._playerCounters[pId][type].incValue(n);
        if (meeple !== null) {
          $(container).insertAdjacentElement('beforeend', meeple);

          if (type == 'conservation') {
            meeple = this._playerCountersMeeples[pId]['conservation-duplicate'];
            container = this.getMeepleContainer({ location: `conservation-duplicate_${newVal}`, pId });
            $(container).insertAdjacentElement('beforeend', meeple);
            this.updateDuplicateConservationBoard();
          }
        }
        return Promise.resolve();
      }

      let tmpElt = `<div style='position:absolute' id='animation-${type}'>${this.formatIcon(type, Math.abs(n))}</div>`;
      this.getVisibleTitleContainer().insertAdjacentHTML('beforebegin', tmpElt);
      let mobileId = `animation-${type}`;
      let counterId = `counter-${pId}-${type}`;

      if (meeple !== null) {
        this.slide(meeple, container);

        // DUPLICATE CONSERVATION
        if (type == 'conservation') {
          meeple = this._playerCountersMeeples[pId]['conservation-duplicate'];
          container = this.getMeepleContainer({ location: `conservation-duplicate_${newVal}`, pId });
          this.slide(meeple, container);
        }
      }

      const slidingDuration = this.getTiming(1200);
      if (n < 0) {
        // Loosing stuff
        this._playerCounters[pId][type].incValue(n);
        return this.slide(mobileId, this.getVisibleTitleContainer(), {
          from: counterId,
          destroy: true,
          phantom: false,
          duration: slidingDuration,
        });
      } else {
        // Gaining stuff
        return this.slide(mobileId, counterId, {
          from: this.getVisibleTitleContainer(),
          destroy: true,
          phantom: false,
          duration: slidingDuration,
        }).then(() => {
          this._playerCounters[pId][type].incValue(n);
          if (type == 'conservation') this.updateDuplicateConservationBoard();
        });
      }
    },

    async notif_getBonuses(n) {
      debug('Notif: getting bonus/gaining resources', n);
      // Update counters promises
      let counters = Object.keys(n.args.bonuses);
      await Promise.all(
        counters.map((type) => {
          if (type == 'source') return;

          let amount = n.args.bonuses[type];
          return this.animatePlayerCounter(n.args.player_id, type, amount);
        })
      );

      if (n.args.score) {
        this._scoreCounters[n.args.player_id].toValue(n.args.score);
      }
    },

    async notif_pilferingMoney(notif) {
      debug('Pilfering money', notif);
      let pId1 = notif.args.player_id;
      let pId2 = notif.args.player_id2;
      let type = 'money';
      let n = notif.args.bonuses.money;

      if (this.isFastMode()) {
        this._playerCounters[pId1][type].incValue(-n);
        this._playerCounters[pId2][type].incValue(n);
        return;
      }

      let tmpElt = `<div style='position:absolute' id='animation-${type}'>${this.formatIcon(type, Math.abs(n))}</div>`;
      this.getVisibleTitleContainer().insertAdjacentHTML('beforebegin', tmpElt);
      let mobileId = `animation-${type}`;
      let counterStartId = `counter-${pId1}-${type}`;
      let counterEndId = `counter-${pId2}-${type}`;

      this._playerCounters[pId1][type].incValue(-n);
      await this.slide(mobileId, $(counterEndId), {
        from: counterStartId,
        destroy: true,
        phantom: false,
        duration: this.getTiming(1200),
      });
      this._playerCounters[pId2][type].incValue(n);
    },

    async notif_gainMarked(n) {
      debug('Notif: gain money from a marked card', n);

      let meeple = $(`meeple-${n.args.token.id}`);
      if (meeple) {
        await this.slideResources(
          [n.args.token],
          {
            to: this.getVisibleTitleContainer(),
            destroy: true,
          },
          false
        );
      }

      await this.notif_getBonuses(n);
    },

    //////////////////////////////////////////////////
    //  ____        _ _     _ _
    // | __ ) _   _(_) | __| (_)_ __   __ _ ___
    // |  _ \| | | | | |/ _` | | '_ \ / _` / __|
    // | |_) | |_| | | | (_| | | | | | (_| \__ \
    // |____/ \__,_|_|_|\__,_|_|_| |_|\__, |___/
    //                                |___/
    //////////////////////////////////////////////////

    setupBuildings() {
      // This function is refreshUI compatible
      let buildingIds = this.gamedatas.buildings.map((building) => {
        if (!$(`building-${building.id}`)) {
          this.addBuilding(building);
        }

        let o = $(`building-${building.id}`);
        if (!o) return null;

        let container = this.getBuildingContainer(building);
        if (o.parentNode != $(container)) {
          dojo.place(o, container);
        }
        o.dataset.state = building.state;
        o.dataset.rotation = building.rotation;
        this.placeBuilding(o, building.x, building.y);

        return building.id;
      });
      document.querySelectorAll('.building-container').forEach((oBuilding) => {
        if (!buildingIds.includes(parseInt(oBuilding.getAttribute('data-id')))) {
          this.destroy(oBuilding);
        }
      });
    },

    addBuilding(building, container = null) {
      if (container === null) {
        container = this.getBuildingContainer(building);
      }

      let o = this.place('tplBuilding', building, container);
      this.placeBuilding(`building-${building.id}`, building.x, building.y);
      this.attachRegisteredTooltips();
    },

    getBuildingContainer(building) {
      if (building.location == 'board') {
        return $(`zoo-map-${building.pId}`).querySelector('.zoo-board');
      } else if (building.location == 'hold') {
        return $('arknova-hold');
      }
      return building.location;
    },

    tplBuilding(building, id = null) {
      // Special enclosure cubes
      let cubes = '';
      const cubesMap = {
        'petting-zoo': 3,
        'reptile-house': 5,
        'large-bird-aviary': 5,
        'small-aquarium': 2,
        'large-aquarium': 5,
        'underwater-tunnel': 2,
      };
      if (cubesMap[building.type]) {
        const color = this.getPlayerColor(building.pId);
        for (let i = 0; i < cubesMap[building.type]; i++) {
          cubes += `<div class="arknova-meeple arknova-icon icon-token" data-type="token" data-color="${color}"></div>`;
        }
      }

      id = id || `building-${building.id}`;
      let tooltips = {
        kiosk: _(
          'Kiosk: must always be at least 3 spaces from every other kiosk on your zoo map. During break, take 1 money for each unique building, special enclosure, occupied standard enclosure, and pavilion adjacent to it.'
        ),
        pavilion: _('Pavilion: increase the appeal of your zoo by 1 when played.'),
        'reptile-house': _(
          'Reptile House: all reptiles can be accomodated either in a standard enclosure or in this special enclosure. If an animal requires water and/or rock spaces next to its standard enclosure, the same requirement also applies to the respective special enclosure'
        ),
        'large-bird-aviary': _(
          'Large Bird Aviary: some birds can be accomodated either in a standard enclosure or in this special enclosure. If an animal requires water and/or rock spaces next to its standard enclosure, the same requirement also applies to the respective special enclosure'
        ),
        'petting-zoo': _(
          'Petting Zoo: Petting Zoo animals cannot be accommodated in a standard enclosure; only in this special enclosure. No other animals can be accomodated in this special enclosure.'
        ),
      };
      if (tooltips[building.type]) {
        this.registerCustomTooltip(tooltips[building.type], id);
      }

      return `<div id="${id}" data-id="${building.id}" class='building-container' data-type='${building.type}' data-state='${building.state}' data-rotation='${building.rotation}'>
      <div class='building-inner'>${cubes}</div>
      <div class='building-border'></div>
      <div class='building-crosshairs'>
        <svg><use href="#crosshairs-svg" /></svg>
      </div>
    </div>`;
    },

    // Place a building at the correct grid position to make at pos (x,y)
    placeBuilding(buildingId, x, y) {
      if (!$(buildingId)) return;

      let buildingType = $(buildingId).dataset.type;
      // let offsets = ENCLOSURES_OFFSETS[buildingType];
      let col = 3 * parseInt(x) + 1; // + (2 - offsets.x);
      let row = parseInt(y) + 1; // + (1 - offsets.y);
      $(buildingId).style.gridColumnStart = col;
      $(buildingId).style.gridRowStart = row;
    },

    async notif_buyBuilding(n) {
      debug('Notif: buying a building', n);
      this._playerCounters[n.args.player_id]['money'].toValue(n.args.total);
      let building = n.args.building;
      let buildingId = `building-${building.id}`;
      this.addBuilding(building);

      if (!this.isFastMode()) {
        let mobileElt = $(buildingId).cloneNode(true);
        mobileElt.id += '_sliding';
        $(buildingId).classList.add('phantom');
        await this.slide(mobileElt, buildingId, {
          from: 'page-title',
          destroy: true,
          phantom: false,
          duration: this.getTiming(1200),
        });
        $(buildingId).classList.remove('phantom');
      }
    },

    async notif_increaseSize(n) {
      debug('Notif: increasing size of a building', n);
      let oldBuilding = n.args.building,
        newBuilding = n.args.newBuilding;
      let buildingId = `building-${newBuilding.id}`;
      this.addBuilding(newBuilding);

      if (!this.isFastMode()) {
        let mobileElt = $(buildingId).cloneNode(true);
        mobileElt.id += '_sliding';
        $(buildingId).classList.add('phantom');
        await this.slide(mobileElt, buildingId, {
          from: 'page-title',
          destroy: true,
          phantom: false,
          duration: this.getTiming(1200),
        });
        $(buildingId).classList.remove('phantom');
      }

      $(`building-${oldBuilding.id}`).remove();
    },

    async notif_cutDown(n) {
      debug('Notif: cutdown a building', n);

      await this.slide(`building-${n.args.buildingId}`, this.getVisibleTitleContainer(), {
        destroy: true,
        phantom: false,
      });

      await this.notif_getBonuses(n);
    },

    async notif_reconstructionRemove(n) {
      debug('Notif: removing building for repositioning', n);

      await Promise.all(
        n.args.buildings.map((building) => {
          return this.slide(`building-${building.id}`, 'arknova-hold');
        })
      );
    },

    async notif_reconstructionPlaceBack(n) {
      debug('Notif: placing back building', n);

      let building = n.args.building;
      let buildingId = `building-${building.id}`;
      $(buildingId).dataset.rotation = building.rotation;

      if (this.isFastMode()) {
        this.placeBuilding(buildingId, building.x, building.y);
      } else {
        let oExistingBuilding = $(buildingId);
        let oNewBuilding = oExistingBuilding.cloneNode(true);
        oExistingBuilding.id += '_sliding';
        this.getBuildingContainer(building).insertAdjacentElement('beforeend', oNewBuilding);
        this.placeBuilding(buildingId, building.x, building.y);
        oNewBuilding.classList.add('phantom');

        await this.slide(oExistingBuilding, oNewBuilding, {
          destroy: true,
          phantom: false,
        });
        $(buildingId).classList.remove('phantom');
      }
    },

    /////////////////////////////////////////////////////////////////////////////////
    //  ___                      ____
    // |_ _|___ ___  _ __  ___  / ___| _   _ _ __ ___  _ __ ___   __ _ _ __ _   _
    //  | |/ __/ _ \| '_ \/ __| \___ \| | | | '_ ` _ \| '_ ` _ \ / _` | '__| | | |
    //  | | (_| (_) | | | \__ \  ___) | |_| | | | | | | | | | | | (_| | |  | |_| |
    // |___\___\___/|_| |_|___/ |____/ \__,_|_| |_| |_|_| |_| |_|\__,_|_|   \__, |
    //                                                                      |___/
    /////////////////////////////////////////////////////////////////////////////////

    tplIconsSummary(player, zooMap = false) {
      let prefix = zooMap ? 'map-' : '';
      let iconSummary = `<div class="icons-summary" id="icons-summary-${prefix}${player.id}">`;
      ICONS_SUMMARY.forEach((summaryRow) => {
        iconSummary += '<div class="icons-summary-row">';
        summaryRow.forEach(
          (icon) =>
            (iconSummary += `<div class="icon-counter">
          <span id="icons-${prefix}${player.id}-${icon}"></span>
          <span class="badge-icon" data-type="${icon}"></span>
        </div>`)
        );
        iconSummary += '</div>';
      });
      iconSummary += '</div>';
      return iconSummary;
    },

    updatePlayersIconsSummaries() {
      this.forEachPlayer((player) => {
        ICONS_SUMMARY.forEach((summaryRow) => {
          summaryRow.forEach((icon) => {
            if (!player.icons) return;

            let val = player.icons[icon] || 0;
            let container = $(`icons-${player.id}-${icon}`);
            container.innerHTML = val;
            container.parentNode.classList.toggle('empty', val == 0);

            container = $(`icons-map-${player.id}-${icon}`);
            container.innerHTML = val;
            container.parentNode.classList.toggle('empty', val == 0);
          });
        });

        ANIMALS_SIZES.forEach((size) => {
          if (!player.sizes) return;

          let val = player.sizes[size] || 0;
          let container = $(`icons-${player.id}-${size}-animals`);
          container.innerHTML = val;
        });
      });
    },

    onChangePlayerIconSummarySetting(val) {
      this.updateIconSummaryPosition(this.player_id, val);
    },

    onChangeOpponentIconSummarySetting(val) {
      this.forEachPlayer((player) => {
        if (player.id != this.player_id) {
          this.updateIconSummaryPosition(player.id, val);
        }
      });
    },

    updateIconSummaryPosition(pId, val) {
      // val = 0 : zoo map
      // val = 1 : player panel
      // val = 2 : both
      let containerInPanel = $(`icons-summary-${pId}`);
      let containerInMap = $(`icons-summary-map-${pId}`);
      if (!containerInPanel || !containerInMap) return;

      // Player panel
      containerInPanel.classList.toggle('hidden', val == 0);
      // Zoo Map
      containerInMap.classList.toggle('hidden', val == 1);
    },
  });
});
