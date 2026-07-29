define(['dojo', 'dojo/_base/declare'], (dojo, declare) => {
  const MAJOR = 'major';
  const HAND_CARDS = 108;
  const HIGHLIGHT_SHARED_SCORING = 111;
  const ACTION_CARD_DISPLAY = 150;

  return declare('agricola.cards', null, {
    setupPlayerCards() {
      // Create an overlay for card animations
      dojo.place("<div id='card-overlay'></div>", 'ebd-body');
      dojo.connect($('card-overlay'), 'click', () => {
        // Skip the click synthesized right after a pinch/pan gesture
        if (this._overlaySwallowClickUntil && Date.now() < this._overlaySwallowClickUntil) return;
        this.zoomOffCard();
      });
      this.setupCardOverlayGestures();

      this.setupMajorsImprovements();
      this.setupHandModal();
      this.updatePlayerCards();
      this.updateHandContainer();
      if (this.isActionCardDisplayCentral()) {
        this.moveActionCardsToHolder(true);
      }
      this.setupCardRulingsModal();

      if (!this.isSpectator) {
        this.updateHandCards();

        //        ['hand-container-minor', 'hand-container-occupation'].forEach((container) => {
        ['hand-container'].forEach((container) => {
          sortable('#' + container, {
            handle: '.card-icon',
            forcePlaceholderSize: true,
            placeholderClass: 'card-placeholder',
          });

          $(container).addEventListener('sortstart', (e) => {
            let cardId = e.detail.item.getAttribute('data-id');
            this.closeTooltip(cardId);
            this._dragndropMode = true;
          });

          $(container).addEventListener('sortstop', (e) => {
            this._dragndropMode = false;
          });

          $(container).addEventListener('sortupdate', (e) => {
            let ids = e.detail.destination.items.map((element) => element.getAttribute('data-id'));
            this.takeAction('actOrderCards', { cardIds: JSON.stringify(ids), lock: false }, false);
          });
        });
      }
    },

    // Place Hand container at the correct spot
    updateHandContainer() {
      let isDraft = this._isDraft;
      dojo.toggleClass('draft-wrapper', 'active', isDraft);
      dojo.setStyle('hand-button', 'display', isDraft || this.gamedatas.isBeginner ? 'none' : 'block');

      let container = isDraft ? 'draft-wrapper' : 'popin_showHand_contents';
      if (!isDraft && this.prefs[HAND_CARDS].value != 0) {
        if (this.prefs[HAND_CARDS].value == 2 || this.prefs[HAND_CARDS].value == 3) {
          container = 'alternative-hand-wrapper';
        } else {
          container = dojo.hasClass('player-boards', 'player-boards-right') ? 'player-boards-left-column' : 'player-boards-separator';
        }

        dojo.setStyle('hand-button', 'display', 'none');
      }
      if (!$(container) || !$('hand-container')) return;
      dojo.place('hand-container', container);
      dojo.toggleClass('alternative-hand-wrapper', 'align-left', this.prefs[HAND_CARDS].value == 3);
      let sep = $('player-boards-separator');
      if (sep) sep.style.display = container === 'player-boards-separator' ? '' : 'none';
    },

    /**
     * Move player action cards between player tableaus and the central grouped holder
     */
    moveActionCardsToHolder(toCentral) {
      let hasActionCards = false;

      this.forEachPlayer((player) => {
        let pId = player.id;
        let cardsWrapper = $('cards-wrapper-' + pId);
        let groupContainer = $('action-cards-group-' + pId);
        if (!cardsWrapper || !groupContainer) return;

        if (toCentral) {
          // Move action cards from player tableau to central holder
          let actionCards = Array.from(cardsWrapper.querySelectorAll('.player-card'))
            .filter((el) => {
              let cardData = this._cardStorage[el.getAttribute('data-id')];
              return this.shouldDisplayAsActionCard(cardData);
            });
          actionCards.forEach((el) => dojo.place(el, groupContainer));
          if (actionCards.length > 0) hasActionCards = true;
        } else {
          // Move action cards back to player tableau in correct order
          let actionCards = Array.from(groupContainer.querySelectorAll('.player-card'));
          actionCards.forEach((el) => {
            let order = parseInt(el.getAttribute('data-play-order') || '0');
            let siblings = Array.from(cardsWrapper.querySelectorAll('.player-card'));
            let insertBefore = siblings.find((s) => parseInt(s.getAttribute('data-play-order') || '0') > order);
            if (insertBefore) {
              cardsWrapper.insertBefore(el, insertBefore);
            } else {
              cardsWrapper.appendChild(el);
            }
          });
        }

        // Hide empty groups
        let group = groupContainer.parentNode;
        dojo.toggleClass(group, 'empty', groupContainer.children.length == 0);
      });

      // Also check non-empty groups if switching to central
      if (toCentral) {
        document.querySelectorAll('.action-cards-player-cards').forEach((gc) => {
          if (gc.children.length > 0) hasActionCards = true;
        });
      }

      dojo.style('action-cards-holder', 'display', toCentral && hasActionCards ? 'block' : 'none');
      this.updatePlayerBoardDimensions();
      this.updateActionCardsHolderMinHeight();
    },

    updateActionCardsHolderVisibility() {
      if (!this.isActionCardDisplayCentral()) {
        dojo.style('action-cards-holder', 'display', 'none');
        this.updateActionCardsHolderMinHeight();
        return;
      }
      let hasCards = false;
      document.querySelectorAll('.action-cards-player-cards').forEach((gc) => {
        let groupHasCards = gc.children.length > 0;
        dojo.toggleClass(gc.parentNode, 'empty', !groupHasCards);
        if (groupHasCards) hasCards = true;
      });
      dojo.style('action-cards-holder', 'display', hasCards ? 'block' : 'none');
      this.updateActionCardsHolderMinHeight();
    },

    // When left column is position:absolute (player-boards-right mode),
    // the holder doesn't contribute to flow height. Compensate via min-height.
    updateActionCardsHolderMinHeight() {
      let holder = $('action-cards-holder');
      let wrapper = $('position-wrapper');
      if (!holder || !wrapper) return;
      if (!dojo.hasClass('player-boards', 'player-boards-right') || holder.style.display == 'none') {
        wrapper.style.minHeight = '';
        return;
      }
      let holderRect = holder.getBoundingClientRect();
      let wrapperRect = wrapper.getBoundingClientRect();
      let neededHeight = holderRect.bottom - wrapperRect.top;
      wrapper.style.minHeight = neededHeight + 'px';
    },

    updatePlayerCards() {
      // This function is refreshUI compatible
      let cardIds = this.gamedatas.playerCards.map((card) => {
        this.loadSaveCard(card);
        // Create the card if needed
        if (!$(card.id)) {
          this.addCard(card);
        }
        // Move the card if not correct container
        let o = $(card.id);
        let container = this.getCardContainer(card);
        if (o.parentNode != $(container)) {
          dojo.place(o, container);
          dojo.toggleClass(o, 'mini', card.mini || card.location == 'inPlay');
        }
        if (card.location == 'inPlay') {
          o.setAttribute('data-play-order', card.state || 0);
        }

        // Sync DOM .stats with fresh gamedatas so turn restart resets the tooltip.
        // notif_updateCardStats writes directly to o.stats during play; refreshUI must reset it.
        o.stats = card.stats;
        o.usedText = card.usedText;

        // Refresh bonusVP
        let counter = document.querySelector('#' + card.id + ' .card-bonus-vp-counter');
        counter.innerHTML = card.bonusVp;
        this.refreshCardTooltipBonusVp(card.id, card.bonusVp);

        // Set up or refresh infobox
        if (card.infobox == null) {
          this.removeInfobox(card);
        }
        else {
          if (dojo.query('.infobox', card.id).length == 0) {
            this.placeInfobox(card);
          } else {
            this.updateInfobox(card);
          }
        }

        // Mark usable harvest cards
        if (card.marked) {
          this.markUsableExchange(card.id);
        }

        // Highlight shared scoring cards
        this.maybeAddSharedScoringHighlight(o, card);

        return card.id;
      });

      // All the cards not in specified list must be destroyed
      document.querySelectorAll('.player-card').forEach((oCard) => {
        if (!cardIds.includes(oCard.getAttribute('data-id'))) {
          dojo.destroy(oCard);
        }
      });

      this.updatePlayerBoardDimensions();
      this.updateActionCardsHolderVisibility();
    },

    updateHandCards() {
      let player = this.gamedatas.players[this.player_id];
      let cards = player.hand ? player.hand : [];
      dojo.empty('hand-container');
      cards.sort((a, b) => a.state - b.state);
      cards.forEach((card) => this.addCard(card));
    },

    /**
     * Create the modal that holds the major improvements
     */
    setupMajorsImprovements() {
      const MAJORS = [
        'Fireplace1',
        'Fireplace2',
        'CookingHearth1',
        'CookingHearth2',
        'Well',
        'ClayOven',
        'StoneOven',
        'Joinery',
        'Pottery',
        'Basket',
      ];

      this._majorsDialog = new customgame.modal('showMajors', {
        class: 'agricola_popin',
        closeIcon: 'fa-times',
        //        openAnimation: true,
        //        openAnimationTarget: 'majors-button',
        contents:
          `
          <div id="majors-container">
            ` +
          MAJORS.map((id) => `<div class="major-holder" id="Major_${id}_holder"></div>`).join('') +
          `
          </div>
        `,
        closeAction: 'hide',
        statusElt: 'majors-button',
        verticalAlign: 'flex-start',
        scale: 0.8,
        breakpoint: 1200,
      });

      this.addCustomTooltip('majors-button', _('Display available major improvements'));
      this.onClick('majors-button', () => this.openMajorsModal(), false);
    },

    /**
     * Open the major improvement modal
     */
    openMajorsModal() {
      this._majorsDialog.show();
    },

    /**
     * Create the modal that holds the cards in hand
     */
    setupHandModal() {
      this._handDialog = new customgame.modal('showHand', {
        class: 'agricola_popin',
        closeIcon: 'fa-times',
        contents: "<div id='hand-container'></div>",
        //          "<div id='hand-container'><div id='hand-container-occupation'></div><div id='hand-container-minor'></div></div>",
        closeAction: 'hide',
        statusElt: 'hand-button',
        verticalAlign: 'flex-start',
        scale: 0.8,
        breakpoint: 1200,
      });

      this.addCustomTooltip('hand-button', _('Display cards in hand'));
      this.onClick('hand-button', () => this.openHandModal(), false);
    },

    /**
     * Open the hand modal
     */
    openHandModal() {
      this._handDialog.show();
    },
    
    /**
     * Create the modal for card rulings
     */
    setupCardRulingsModal() {
      if ($('popin_showCardRulings_container')) {
        dojo.destroy('popin_showCardRulings_container');
      }

      this._cardRulingsDialog = new customgame.modal('showCardRulings', {
        class: 'agricola_popin',
        closeIcon: 'fa-times',
        closeAction: 'hide',
        verticalAlign: 'center',

        openAnimation: true,
        openAnimationTarget: null,

        title: '',
        contents: `
          <div id="card-rulings-layout">
            <div id="card-rulings-preview"></div>
            <div id="card-rulings-text"></div>
          </div>
        `,
      });
      this.fixModalToViewport('showCardRulings');
    },

    fixModalToViewport(id) {
      const container = $(`popin_${id}_container`);
      const underlay = $(`popin_${id}_underlay`);
      const wrapper = $(`popin_${id}_wrapper`);

      if (!container || !underlay || !wrapper) {
        return;
      }

      container.style.position = 'fixed';
      container.style.left = '0';
      container.style.top = '0';
      container.style.width = '100vw';
      container.style.height = '100vh';

      underlay.style.position = 'fixed';
      underlay.style.left = '0';
      underlay.style.top = '0';
      underlay.style.width = '100vw';
      underlay.style.height = '100vh';

      wrapper.style.position = 'fixed';
      wrapper.style.left = '0';
      wrapper.style.top = '0';
      wrapper.style.width = '100vw';
      wrapper.style.height = '100vh';

      container.style.zIndex = '9999';
      underlay.style.zIndex = '9999';
      wrapper.style.zIndex = '10000';

      // Prevent the close icon from jumping the page to the top
      const close = $(`popin_${id}_close`);
      if (close) {
        close.setAttribute('href', 'javascript:void(0)');
        close.addEventListener(
          'click',
          (e) => {
            e.preventDefault();
          },
          true
        );
      }
    },

    getRulingsCardPreviewNode(card) {
      const c = Object.assign({}, card, { mini: false });

      const tmp = document.createElement('div');
      tmp.innerHTML = this.tplPlayerCard(c, false);

      const node = tmp.firstElementChild;

      // Avoid id collisions with the real card element in DOM
      node.id = 'rulings_' + card.id;
      node.classList.add('rulings-preview');

      // Remove zoom control inside the preview
      const zoom = node.querySelector('.player-card-zoom');
      if (zoom) {
        zoom.remove();
      }

      // Swallow pointer/click events so it never interacts with the game (or closes zoom)
      const swallow = (evt) => {
        evt.preventDefault();
        evt.stopPropagation();
        evt.stopImmediatePropagation();
      };

      ['click', 'mousedown', 'mouseup', 'pointerdown', 'pointerup', 'touchstart', 'touchend'].forEach((t) => {
        node.addEventListener(t, swallow, true);
      });

      return node;
    },

    showCardRulingsModal(card, openAnimationTarget = null) {
      if (!card) return;

      if (!this._cardRulingsDialog) {
        this.setupCardRulingsModal();
      }

      this._cardRulingsDialog.openAnimationTarget = openAnimationTarget;

      // Dark underlay
      const underlay = $('popin_showCardRulings_underlay');
      if (underlay) underlay.style.backgroundColor = '#000';

      // Popin sizing
      const popin = $('popin_showCardRulings');
      if (popin) {
        popin.style.width = 'min(860px, 92vw)';
        popin.style.maxWidth = '860px';
        popin.style.boxSizing = 'border-box';
      }

      // Layout (just the one important alignment)
      const layout = $('card-rulings-layout');
      if (layout) {
        layout.style.display = 'flex';
        layout.style.gap = '18px';
        layout.style.alignItems = 'stretch';
      }

      // Title
      const titleEl = $('popin_showCardRulings_title');
      if (titleEl) titleEl.innerHTML = _(card.name || '');

      // Left: card preview
      const preview = $('card-rulings-preview');
      if (preview) {
        dojo.empty(preview);

        preview.style.setProperty('--agricolaCardWidth', '188px');
        preview.style.setProperty('--agricolaCardHeight', '299px');
        preview.style.setProperty('--agricolaCardScale', '0.8');

        dojo.place(this.getRulingsCardPreviewNode(card), preview);
      }

      // Right: rulings lines (no <ul>/<li>)
      const rulings = Array.isArray(card.rulings) ? card.rulings : [];
      const linesHtml = (rulings.length ? rulings : [''])
        .map((line) => `<div class="agr-rulings__line">${this.formatRulingsLine(line)}</div>`)
        .join('');

      const text = $('card-rulings-text');
      if (text) {
        text.innerHTML = `<div class="agr-rulings">${linesHtml}</div>`;

        const wrap = text.querySelector('.agr-rulings');

        const sync = () => {
          if (!layout || !preview || !wrap) return;

          const isColumn = getComputedStyle(layout).flexDirection === 'column';
          if (isColumn) {
            text.style.height = '';
            wrap.classList.remove('is-centered');
            return;
          }

          // Measure the actual rendered card element (preview container can report 0)
          const cardEl =
            preview.querySelector('.player-card') ||
            preview.querySelector('.card') ||
            preview.firstElementChild;

          const rect = cardEl ? cardEl.getBoundingClientRect() : preview.getBoundingClientRect();
          const ph = rect.height;

          if (ph > 50) {
            text.style.height = `${Math.round(ph)}px`;
          } else {
            text.style.height = '';
          }

          const fits = wrap.scrollHeight <= text.clientHeight + 1;
          wrap.classList.toggle('is-centered', fits);
        };

        requestAnimationFrame(() => requestAnimationFrame(sync));
      }

      this._cardRulingsDialog.show();
    },

    formatRulingsLine(s) {
      let out = _(s);
      out = out.replace(/__([^_]+)__/g, '<span class="action-card-name-reference">$1</span>');
      out = this.formatStringMeeples(out);
      return out;
    },

    getCardById(cardId) {
      const inPlay = (this.gamedatas.playerCards || []).find((c) => c.id === cardId);
      if (inPlay) return inPlay;

      const hand = (this.gamedatas.players?.[this.player_id]?.hand || []).find((c) => c.id === cardId);
      if (hand) return hand;

      return null;
    },

    /**
     * Smarter buffering of card details (to not resend them over notification again)
     */
    loadSaveCard(card) {
      if (card.desc) {
        // Card contains all info => save it in cardStorage
        this._cardStorage[card.id] = card;
      } else {
        // Card is missing information : load it from cardStorage
        if (this._cardStorage[card.id] === undefined) {
          console.error('Missing card informations :', card);
          return;
        }

        // Only static card info — stats/bonusVp/infobox/marked are dynamic and flow
        // directly from server notifications or gamedatas, so never copy them here.
        [
          'actionCard',
          'category',
          'costText',
          'costs',
          'deck',
          'desc',
          'extraVp',
          'fee',
          'field',
          'holder',
          'animalHolder',
          'name',
          'numbering',
          'passing',
          'players',
          'prerequisite',
          'sharedScoring',
          'tooltip',
          'type',
          'usedText',
          'vp',
        ].forEach((info) => (card[info] = this._cardStorage[card.id][info]));
      }
    },

    /**
     * Add a card to its default location
     */
    addCard(card, container = null) {
      this.loadSaveCard(card);
      card.description = this.formatCardDesc(card.desc);
      card.mini = card.mini ? card.mini : card.location == 'inPlay';

      if (container == null) {
        container = this.getCardContainer(card);
      }

      // Some locations should not be rendered (e.g. discardpile)
      if (container === false) {
        return;
      }

      let oCard = this.place('tplPlayerCard', card, container);
      oCard.usedText = card.usedText;
      if (card.location == 'inPlay') {
        oCard.setAttribute('data-play-order', card.state || 0);
      }
      this.addCustomTooltip(card.id, this.tplPlayerCard(card, true));
      if (card.animalHolder) {
        setTimeout(() => {
          let animalsWrapper = oCard.querySelector('.animals-control');
          dojo.connect(animalsWrapper, 'mouseenter', (evt) => {
            evt.stopPropagation();
          });
        }, 1);
      }
      // Hand cards are zoomable too: the hover button stays mini-only (CSS), but touch gets tap-to-zoom
      let zoomable = card.mini || card.location == 'hand' || card.location == 'selection';
      if (zoomable && !oCard.dataset.zoomWired) {
        oCard.dataset.zoomWired = '1';
        oCard.querySelector('.player-card-zoom').addEventListener('click', () => {
          this.zoomOnCard(card.id);
        });
        // On touch, the zoom button is only visible after CSS :hover activates on the first tap.
        // Trigger zoom directly on touchend so it works on the first tap.
        let touchStartX, touchStartY;
        oCard.querySelector('.player-card-resizable').addEventListener('touchstart', (evt) => {
          touchStartX = evt.touches[0].clientX;
          touchStartY = evt.touches[0].clientY;
        }, { passive: true });
        oCard.querySelector('.player-card-resizable').addEventListener('touchend', (evt) => {
          if (touchStartX == null) return;
          const touch = evt.changedTouches[0];
          if (Math.abs(touch.clientX - touchStartX) > 10 || Math.abs(touch.clientY - touchStartY) > 10) return;
          touchStartX = null;
          if (oCard.classList.contains('selectable')) return; // action card: let the synthetic click fire
          evt.preventDefault(); // suppress synthetic click so zoom button click handler doesn't double-fire
          this.zoomOnCard(card.id);
        });
      }

      this.maybeAddCardRulingsIcon(oCard, card);
      this.maybeAddSharedScoringHighlight(oCard, card);

      if (card.pId) {
        this.updatePlayerBoardDimensions(card.pId);
      }
    },

    isSharedScoringHighlightEnabled() {
      if (!this.prefs || !this.prefs[HIGHLIGHT_SHARED_SCORING]) {
        return false;
      }
      return Number(this.prefs[HIGHLIGHT_SHARED_SCORING].value) === 1;
    },

    maybeAddSharedScoringHighlight(oCard, card) {
      let inner = oCard.querySelector('.player-card-inner');
      if (card.sharedScoring && card.location == 'inPlay' && this.isSharedScoringHighlightEnabled()) {
        dojo.addClass(oCard, 'shared-scoring');
        if (!inner.querySelector('.shared-scoring-sash')) {
          let sash = document.createElement('div');
          sash.className = 'shared-scoring-sash';
          sash.innerHTML = '<div class="shared-scoring-icon"></div>';
          inner.appendChild(sash);
        }
      } else {
        dojo.removeClass(oCard, 'shared-scoring');
        if (inner) {
          let sash = inner.querySelector('.shared-scoring-sash');
          if (sash) sash.remove();
        }
      }
    },

    refreshSharedScoringHighlights() {
      dojo.query('.player-card').forEach((node) => {
        let cardId = node.getAttribute('data-id');
        let card = this._cardStorage[cardId];
        if (card) {
          this.maybeAddSharedScoringHighlight(node, card);
        }
      });
    },

    isCardRulingsIconEnabled() {
      const OPTION_CARD_RULINGS_ICON = 110;

      if (!this.prefs || !this.prefs[OPTION_CARD_RULINGS_ICON]) {
        return true;
      }

      return Number(this.prefs[OPTION_CARD_RULINGS_ICON].value) === 1;
    },

    maybeAddCardRulingsIcon(oCard, card) {
      if (!this.isCardRulingsIconEnabled()) {
        return;
      }

      if (!card || !Array.isArray(card.rulings) || card.rulings.length === 0) {
        return;
      }

      const anchor = oCard.querySelector('.card-desc');
      if (!anchor) {
        return;
      }

      if (anchor.querySelector('.card-rulings-icon')) {
        return;
      }

      dojo.addClass(oCard, 'has-rulings');

      const btn = document.createElement('div');
      btn.className = 'card-rulings-icon';
      btn.setAttribute('role', 'button');
      btn.setAttribute('tabindex', '0');
      btn.title = _('Card rulings');
      btn.textContent = '?';

      const open = (evt) => {
        evt.preventDefault();
        evt.stopPropagation();

        const stableAnchor = $(card.id) || btn;

        const doOpen = () => this.showCardRulingsModal(card, stableAnchor);

        if (this._zoomedCard != null) {
          this.zoomOffCardInstant({}).then(() => doOpen());
        } else {
          doOpen();
        }
      };

      btn.addEventListener('click', open);

      // Prevent the card click (zoom) from stealing this click
      btn.addEventListener('mousedown', (evt) => evt.stopPropagation());
      btn.addEventListener('touchstart', (evt) => evt.stopPropagation());
      btn.addEventListener('keydown', (evt) => {
        if (evt.key === 'Enter' || evt.key === ' ') {
          open(evt);
        }
      });

      anchor.appendChild(btn);
    },

    refreshCardRulingsIcons() {
      const OPTION_CARD_RULINGS_ICON = 110;

      const enabled =
        !this.prefs || !this.prefs[OPTION_CARD_RULINGS_ICON]
          ? true
          : Number(this.prefs[OPTION_CARD_RULINGS_ICON].value) === 1;

      dojo.query('.player-card').forEach((node) => {
        // Remove any existing icon and class first
        node.querySelectorAll('.card-rulings-icon').forEach((n) => n.remove());
        dojo.removeClass(node, 'has-rulings');

        if (!enabled) {
          return;
        }

        const cardId =
          node.getAttribute('data-id') ||
          (node.id ? node.id.replace(/^tooltip_/, '') : null);

        if (!cardId) {
          return;
        }

        const card = (typeof this.getCardById === 'function' ? this.getCardById(cardId) : null)
          || (this.gamedatas?.playerCards || []).find((c) => c.id === cardId)
          || null;

        if (!card) {
          return;
        }

        this.maybeAddCardRulingsIcon(node, card);
      });
    },

    refreshCardTooltipBonusVp(cardId, newValue) {
      let content = this.tooltips[cardId].label;
      content = content.replace(
        /<div class='card-bonus-vp-counter'>[0-9]*<\/div>/g,
        `<div class='card-bonus-vp-counter'>${newValue}</div>`,
      );
      this.tooltips[cardId].label = content;
    },

    isActionCardDisplayCentral() {
      return this.prefs[ACTION_CARD_DISPLAY] && this.prefs[ACTION_CARD_DISPLAY].value == 1;
    },

    shouldDisplayAsActionCard(cardData) {
      if (!cardData || !cardData.actionCard) return false;
      // Basket Chair is a private space, not a real action space for workers
      if (cardData.id == 'C22_BasketChair') return false;
      // Studio Boat only acts as an action card in 1-3 player games
      if (cardData.id == 'C39_StudioBoat' && Object.keys(this.gamedatas.players).length >= 4) return false;
      return true;
    },

    getCardContainer(card) {
      if (card.location == 'inPlay') {
        // Action card in central mode => in the grouped holder
        if (this.shouldDisplayAsActionCard(card) && this.isActionCardDisplayCentral()) {
          return 'action-cards-group-' + card.pId;
        }
        // Played card => in front of player
        return 'cards-wrapper-' + card.pId;
      } else if (card.type == MAJOR && card.location == 'board') {
        // Available major => in the major modal
        return card.id + '_holder';
      } else if (card.location == 'hand' || card.location == 'selection') {
        // Card in hand => in the 'hand' modal
        return 'hand-container';
        //        return 'hand-container-' + card.type;
      } else if (card.location == 'draft') {
        return 'draft-container';
      } else if (card.location == 'livingHandOffer' || card.location == 'livingDraft') {
        // Living Hand offers are private: only render for the owning player
        return parseInt(card.pId) === parseInt(this.player_id) ? 'draft-container' : false;
      } else if (card.location == 'discardpile' || card.location == 'phase2') {
        // Don't render on the board
        return false;
      }

      console.error('Trying to get container of a card', card);
      return 'game_play_area';
    },

    /**
     * Template for all "player" cards (Improvements and Occupations)
     */
    tplPlayerCard(card, tooltip = false) {
      let formatStr = (s) => _(s).replace(/__([^_]+)__/g, '<span class="action-card-name-reference">$1</span>');

      let costText = card.costText == '' ? '' : formatStr(card.costText);
      let prerequisite = card.prerequisite == '' ? '' : formatStr(card.prerequisite);
      let description = card.description;
      if (description === undefined) {
        description = this.formatCardDesc(card.desc);
      }
      if (description === undefined || description === null) {
        description = '';
      }

      let subHolders = '';
      if (card.id == 'D75_WoodField' && !tooltip) {
        subHolders = '<div class="subholder" data-x="0"></div><div class="subholder" data-x="-1"></div>';
      }
      if (card.id == 'E80_RockGarden' && !tooltip) {
        subHolders = '<div class="subholder" data-x="0"></div><div class="subholder" data-x="-1"></div><div class="subholder" data-x="-2"></div>';
      }

      let uid = (tooltip ? 'tooltip_' : '') + card.id;
      return (
        `<div id="${uid}" data-id='${card.id}' data-numbering='${card.numbering}'
          class='player-card ${card.type} ${card.mini && !tooltip ? 'mini' : ''} ${
          card.location == 'selection' ? 'pending' : ''
        } ${card.animalHolder ? 'animalHolder' : ''}'
          data-cook='${card.cook}' data-bread='${card.bread}'
          data-state='${card.state}'>
          <div class='player-card-resizable'>
            <div class='player-card-inner'>
              <div class='card-frame'></div>
              ` +
        (card.passing === true
          ? ''
          : `<div class='card-frame-left-leaves'></div><div class='card-frame-right-leaves'></div>`) +
        `
              <div class='card-icon'></div>
              <div class='card-title'>
                ${_(card.name)}
              </div>
              <div class='card-numbering'>${card.numbering}</div>
              <div class='card-bonus-vp-counter'>${card.bonusVp}</div>
              ` +
        (card.players == undefined ? '' : `<div class='card-players' data-n="${card.players}"></div>`) +
        (card.deck == undefined ? '' : `<div class='card-deck' data-deck="${card.deck}"></div>`) +
        (card.vp != 0 ? `<div class='card-score' data-score="${card.vp}">${card.vp}</div>` : '') +
        (card.extraVp ? `<div class='card-extra-score'></div>` : '') +
        `
              <div class="card-category" data-category="${card.category}"></div>
              <div class="card-cost">
                ` +
        (costText == '' ? '' : `<div class="card-cost-text">${costText}</div>`) +
        `
                ${this.formatCardCost(card)}
              </div>` +
        (prerequisite == ''
          ? ''
          : `<div class="card-prerequisite"><div class="prerequisite-text">${prerequisite}</div></div>`) +
        `
              <div class='card-desc'><div class='card-desc-scroller'>${description}</div></div>
              <div class='card-bottom-left-corner'></div>
              <div class='card-bottom-right-corner'></div>
              ` +
        (card.holder
          ? "<div class='resource-holder farmer-holder resource-holder-update " +
            (card.actionCard ? 'actionCard' : '') +
            `'>${subHolders}</div>`
          : '') +
        `
            </div>
            <div class="player-card-zoom">
              <svg><use href="#zoom-svg" /></svg>
            </div>
          </div>
          ` +
          `
            <div class='player-card-stats'></div>
          ` +
        (card.field ? "<div class='player-card-field-cell'></div>" : '') +
        (!tooltip && card.animalHolder
          ? "<div class='resource-holder resource-holder-update animal-holder' data-n='0'></div>"
          : '') +
        `
        </div>`
      );
    },

    /**
     * Format card cost
     */
    formatCardCost(card) {
      let formatArray = (arr) =>
        Object.keys(arr)
          .map((res) => '<div>' + this.formatStringMeeples(arr[res] + '<' + res.toUpperCase() + '>') + '</div>')
          .join('');

      let formatConditionalCost = (arr) =>
        Object.keys(arr)
          .map((res) => '<div>(' + this.formatStringMeeples(arr[res] + '<' + res.toUpperCase() + '>') + ')</div>')
          .join('');

      if (card.id === 'C40_CanvasSack') {
        card.costs = [{"grain": 1}, {"reed": 1}];
      }

      if (card.id === 'B65_GrainDepot') {
        card.costs = [{"wood": 2}, {"clay": 2}, {"stone": 2}];
      }

      return (
        (card.fee != null ? formatArray(card.fee) + '<div class="card-cost-fee-separator">+</div>' : '') +
        card.costs.map((cost) => formatArray(cost)).join('<div class="card-cost-separator"></div>') +
        (card.conditionalCost != null ? '<div class="card-cost-conditional">' + formatConditionalCost(card.conditionalCost) + '</div>' : '')
      );
    },

    /**
     * Prompt current player to pick a card
     */
    promptCard(types, cards, callback) {
      if (this.isFastMode()) return;

      // Majors
      if (types.includes('major')) {
        dojo.query('#majors-container .player-card').addClass('unselectable');
        this.addPrimaryActionButton('btnOpenMajorModal', _('Show major improvements'), () => this.openMajorsModal());

        if (types.length == 1) {
          // If only major are prompted, auto open modal
          this.openMajorsModal();
        }
      }

      // Hand
      if (types.includes('minor') || types.includes('occupation')) {
        dojo.query('#hand-container .player-card').addClass('unselectable');
        if (this.prefs[HAND_CARDS].value == 0) {
          this.addPrimaryActionButton('btnOpenHandModal', _('Show hand cards'), () => this.openHandModal());

          if (types.length == 1) {
            // If only one type is prompted, auto open modal
            this.openHandModal();
          }
        }
      }

      // Add event listener
      cards.forEach((cardId) => this.onClick(cardId, () => callback(cardId)));
    },

    /**
     * Prompt current player to pick a card
     */
    promptCardMultiple(types, cards, requiredSelections, callback) {
      let selectedCards = [];

      let select = (cId) => {
        dojo.addClass(cId, 'selected');
      };
      let unselect = (cId) => {
        dojo.removeClass(cId, 'selected');
      };

      let onClickCard = (cId) => {        
        // Toggle element
        let i = selectedCards.findIndex((p) => p == cId);
        if (i == -1) {
          select(cId);
          selectedCards.push(cId);
        } else {
          unselect(cId);
          selectedCards.splice(i, 1);
        }

        // Execute callback
        if (selectedCards.length == requiredSelections) {
          if (this._handDialog.isDisplayed()) {
            this._handDialog.hide();
          }
          callback(selectedCards);
        }
      };

      // Hand
      if (types.includes('minor') || types.includes('occupation')) {
        dojo.query('#hand-container .player-card').addClass('unselectable');
        if (this.prefs[HAND_CARDS].value == 0) {
          this.addPrimaryActionButton('btnOpenHandModal', _('Show hand cards'), () => this.openHandModal());

          if (types.length == 1) {
            // If only one type is prompted, auto open modal
            this.openHandModal();
          }
        }
      }

      // Attach event to cards
      cards.forEach((cId) => {
        this.onClick(cId, () => onClickCard(cId));
      });
    },

    computeSlidingAnimationFrom(card, newContainer) {
      let from = 'hand-button';
      if (!$(card.id)) {
        this.addCard(card, newContainer);
        from = 'overall_player_board_' + card.pId;
      } else {
        dojo.place(card.id, newContainer);
        if (card.type == 'major') {
          from = this._majorsDialog.isDisplayed() ? card.id + '_holder' : 'majors-button';
        } else {
          from = this._handDialog.isDisplayed() || this.prefs[HAND_CARDS].value != 0 ? 'hand-container' : 'hand-button';
        }
      }

      this.updatePlayerBoardDimensions();
      return from;
    },

    getCardPreviewWaitTime() {
      const DEFAULT_SPEED = 80;
      const MIN_SPEED = 30;
      const MAX_SPEED = 200;
      const MIN_WAIT_MS = 250;
      const MAX_WAIT_MS = 3000;

      let speed = parseInt(this._cardAnimationSpeed, 10);
      if (!Number.isFinite(speed)) {
        speed = DEFAULT_SPEED;
      }
      speed = Math.max(MIN_SPEED, Math.min(MAX_SPEED, speed));

      const wait = Math.round(80000 / speed);
      return Math.max(MIN_WAIT_MS, Math.min(MAX_WAIT_MS, wait));
    },

    /**
     * Notification when someone bought a card
     */
    notif_buyCard(n) {
      debug('Notif: buying a card', n);
      let card = n.args.card;

      dojo.query('.cards-wrapper .player-card.phantom').removeClass('phantom');

      let duration = 700;
      let waitingTime = this.getCardPreviewWaitTime();

      // Determine target container based on action card display preference
      let targetContainer = this.shouldDisplayAsActionCard(card) && this.isActionCardDisplayCentral()
        ? 'action-cards-group-' + card.pId
        : 'cards-wrapper-' + card.pId;

      // Create the card if needed, and compute initial location of sliding event
      let exists = $(card.id);
      let from = this.computeSlidingAnimationFrom(card, targetContainer);

      // Zoom on it, then zoom off
      if (this.isFastMode() || this.isInstantSpeed()) {
        this.notifqueue.setSynchronousDuration(this.isFastMode() ? 0 : 10);
      } else {
        this.zoomOnCard(card.id, { from, duration })
          .then(() => this.wait(waitingTime))
          .then(() => this.zoomOffCard({ duration }))
          .then(() => this.notifqueue.setSynchronousDuration(10));
      }

      // If the card was already existing, make sure to add event listener for zooming
      // (guarded so we don't stack duplicates on re-buys)
      if (exists) {
        dojo.addClass(card.id, 'mini');
        const existingCard = $(card.id);
        if (!existingCard.dataset.zoomWired) {
          existingCard.dataset.zoomWired = '1';
          existingCard.querySelector('.player-card-zoom').addEventListener('click', () => {
            this.zoomOnCard(card.id);
          });
          let touchStartX, touchStartY;
          existingCard.querySelector('.player-card-resizable').addEventListener('touchstart', (evt) => {
            touchStartX = evt.touches[0].clientX;
            touchStartY = evt.touches[0].clientY;
          }, { passive: true });
          existingCard.querySelector('.player-card-resizable').addEventListener('touchend', (evt) => {
            if (touchStartX == null) return;
            const touch = evt.changedTouches[0];
            if (Math.abs(touch.clientX - touchStartX) > 10 || Math.abs(touch.clientY - touchStartY) > 10) return;
            touchStartX = null;
            if (existingCard.classList.contains('selectable')) return; // action card: let the synthetic click fire
            evt.preventDefault();
            this.zoomOnCard(card.id);
          });
        }
      }

      // Stamp play order for action card toggle ordering
      let oCard = $(card.id);
      if (oCard && card.location == 'inPlay') {
        oCard.setAttribute('data-play-order', card.state || 0);
      }

      // Highlight shared scoring cards
      this.maybeAddSharedScoringHighlight(oCard, card);

      // Update action cards holder visibility
      this.updateActionCardsHolderVisibility();

      // Close major modal if open
      if (this._majorsDialog.isDisplayed()) {
        this._majorsDialog.hide();
      }
      // Close hand modal if open
      if (this._handDialog.isDisplayed()) {
        this._handDialog.hide();
      }

      return null;
    },

    /**
     * Notification when someone bought a card and give it to next player
     */
    notif_buyAndPassCard(n) {
      debug('Notif: buying and passing a card', n);
      let card = n.args.card;
      let receiving = this.player_id == n.args.player_id2;

      let duration = 700;
      let waitingTime = this.getCardPreviewWaitTime();

      // Create the card if needed, and compute initial location of sliding event
      let from = this.computeSlidingAnimationFrom(card, receiving ? 'hand-button' : 'reserve-' + n.args.player_id2);
      if (this.isFastMode() || this.isInstantSpeed()) {
        if (receiving) {
          dojo.place(card.id, 'hand-container');
        } else {
          dojo.destroy(card.id);
        }
        this.notifqueue.setSynchronousDuration(this.isFastMode() ? 0 : 10);
        return;
      }

      // Zoom on it, then zoom off
      this.zoomOnCard(card.id, { from, duration })
        .then(() => this.wait(waitingTime))
        .then(() => this.zoomOffCard({ duration }))
        .then(() => {
          if (receiving) {
            dojo.place(card.id, 'hand-container');
          } else {
            dojo.destroy(card.id);
          }
          this.notifqueue.setSynchronousDuration(10);
        });

      // Close hand modal if open
      if (this._handDialog.isDisplayed()) {
        this._handDialog.hide();
      }
    },

    notif_buyAndDestroyCard(n) {
      debug('Notif: buying and destroying a card', n);
      let card = n.args.card;

      let duration = 700;
      let waitingTime = this.getCardPreviewWaitTime();

      // Create the card if needed, and compute initial location of sliding event
      let from = this.computeSlidingAnimationFrom(card, 'reserve-' + n.args.player_id);

      if (this.isFastMode() || this.isInstantSpeed()) {
        dojo.destroy(card.id);
        this.notifqueue.setSynchronousDuration(this.isFastMode() ? 0 : 10);
        return;
      }

      // Zoom on it, then zoom off
      this.zoomOnCard(card.id, { from, duration })
        .then(() => this.wait(waitingTime))
        .then(() => this.zoomOffCard({ duration }))
        .then(() => {
          dojo.destroy(card.id);
          this.notifqueue.setSynchronousDuration(10);
        });

      // Close hand modal if open
      if (this._handDialog.isDisplayed()) {
        this._handDialog.hide();
      }
    },

      notif_debugCardToHand(n) {
      const card = n.args.card;

      if (String(card.pId) !== String(this.player_id)) {
        return;
      }

      card.location = 'hand';

      if ($(card.id)) {
        dojo.place(card.id, 'hand-container');
        this.addCustomTooltip(card.id, this.tplPlayerCard(card, true));
        this.updatePlayerBoardDimensions(card.pId);
      } else {
        this.addCard(card, 'hand-container');
      }

      this.notifqueue.setSynchronousDuration(0);
    },

    notif_debugCardInPlay(n) {
      const card = n.args.card;

      if (String(card.pId) !== String(this.player_id)) {
        return;
      }

      card.location = 'inPlay';
      this.addCard(card);
      this.notifqueue.setSynchronousDuration(0);
    },

    /**
     * Notification when someone return a card for a payment
     */
    notif_payWithCard(n) {
      debug('Notif: return a card', n);
      let card = n.args.card;
      let holder = $(card.id + '_holder');
      if (this.isFastMode() || this.isInstantSpeed()) {
        if (holder) {
          dojo.place(card.id, card.id + '_holder');
          dojo.removeClass(card.id, 'mini');
        } else {
          dojo.destroy(card.id);
        }
        return;
      }

      dojo.style(card.id, 'transform', 'scale(0.6)');
      this.slide(card.id, 'majors-button').then(() => {
        if (holder) {
          dojo.place(card.id, card.id + '_holder');
          dojo.removeClass(card.id, 'mini');
        } else {
          dojo.destroy(card.id);
        }
      });
      dojo.removeClass(card.id, 'mini');
      dojo.style(card.id, 'transform', 'scale(1)');
    },

    formatStats(stats, card) {
      const resourceToken = (res) => {
        if (res === 'stable') return '<BARN>';
        return '<' + res.replace(/([a-z])([A-Z])/g, '$1_$2').toUpperCase() + '>';
      };

      const parentId = card && card.parentNode ? card.parentNode.id : '';
      const match = parentId.match(/^(?:cards-wrapper|action-cards-group)-(\d+)$/);
      const pId = match ? parseInt(match[1]) : null;
      const playerColor = pId != null && this.gamedatas.players[pId]
        ? this.gamedatas.players[pId].color
        : null;
      const colorAttr = playerColor ? ' data-color="' + playerColor + '"' : '';

      let string = '<div class="content"' + colorAttr + '>';

      // Group stats by category so related lines stay together. Within a
      // category, preserve insertion order (stable sort).
      const categoryOrder = ['used', 'gain', 'receivedPayment', 'paid', 'paidToOthers', 'saved'];
      // Longest prefix first to avoid 'paid' swallowing 'paidToOthers'.
      const prefixCheckOrder = ['receivedPayment', 'paidToOthers', 'used', 'gain', 'saved', 'paid'];
      const categoryOf = (stat) => prefixCheckOrder.find((c) => stat.startsWith(c)) ?? null;
      const ordered = Object.keys(stats).slice().sort((a, b) => {
        return categoryOrder.indexOf(categoryOf(a)) - categoryOrder.indexOf(categoryOf(b));
      });

      ordered.forEach((stat) => {
        let v = stats[stat];
        const vb = '<b>' + v + '</b>';
        let line = null;
        if (stat.startsWith('gain')) {
          const res = stat.substring(4);
          if (res === 'occupation') {
            line = dojo.string.substitute(_('Occupations: ${v}'), { v: vb });
          } else if (res === 'field') {
            line = dojo.string.substitute(_('Plows: ${v}'), { v: vb });
          } else if (res.startsWith('room') || res === 'stable') {
            const R = resourceToken(res);
            line = dojo.string.substitute(_('${R} built: ${v}'), { R, v: vb });
          } else {
            const R = resourceToken(res);
            line = dojo.string.substitute(_('${R} gained: ${v}'), { R, v: vb });
          }
        } else if (stat.startsWith('used')) {
          const label = card && card.usedText ? _(card.usedText) : _('Number of times used');
          line = dojo.string.substitute('${label}: ${v}', { label, v: vb });
        } else if (stat.startsWith('receivedPayment')) {
          const R = resourceToken(stat.substring(15));
          line = dojo.string.substitute(_('${R} from others: ${v}'), { R, v: vb });
        } else if (stat.startsWith('paidToOthers')) {
          const R = resourceToken(stat.substring(12));
          line = dojo.string.substitute(_('${R} to others: ${v}'), { R, v: vb });
        } else if (stat.startsWith('saved')) {
          const R = resourceToken(stat.substring(5));
          line = dojo.string.substitute(_('${R} saved: ${v}'), { R, v: vb });
        } else if (stat.startsWith('paid')) {
          const R = resourceToken(stat.substring(4));
          line = dojo.string.substitute(_('${R} paid: ${v}'), { R, v: vb });
        }
        if (line !== null) {
          string += this.formatStringMeeples(line) + '<br>';
        }
      });

      return string + "</div>";
    },

    placeInfobox(card) {
      html = '<div class="infobox" title="' + card.infobox + '"> ' + card.infobox + ' </div>';
      dojo.place(html, card.id);
      return card;
    },

    updateInfobox(card) {
      dojo.query('.infobox', card.id).forEach(function(node) {
        node.innerHTML = card.infobox;
        node.title = card.infobox;
      });
      return card;
    },

    removeInfobox(card) {
      dojo.query('.infobox', card.id).forEach(function(node) {
        dojo.destroy(node);
      });
      return card;
    },

    markUsableExchange(cardId) {
      dojo.addClass(cardId, 'usable');
    },

    unmarkUsableExchange(cardId) {
      dojo.removeClass(cardId, 'usable');
    },

    unmarkAllUsableExchanges() {
      dojo.query('.usable').forEach((card) => {
        dojo.removeClass(card, 'usable');
      });
    },

    /**
     * iOS Safari can crash (WebContent killed, tab reloads) when a native pinch-zoom forces it to
     * re-rasterize the big scaled-down boards. While the overlay is open, swallow the native
     * gesture and pinch-zoom the overlay card itself instead.
     */
    setupCardOverlayGestures() {
      let overlay = $('card-overlay');

      ['gesturestart', 'gesturechange'].forEach((name) => {
        document.addEventListener(
          name,
          (evt) => {
            if (overlay.classList.contains('active')) evt.preventDefault();
          },
          { passive: false },
        );
      });

      let dist = (touches) => Math.hypot(touches[0].clientX - touches[1].clientX, touches[0].clientY - touches[1].clientY);
      let mid = (touches) => ({
        x: (touches[0].clientX + touches[1].clientX) / 2,
        y: (touches[0].clientY + touches[1].clientY) / 2,
      });
      let gesture = null;

      overlay.addEventListener(
        'touchstart',
        (evt) => {
          let zoom = this._overlayZoom;
          if (!zoom) return;
          if (evt.touches.length == 2) {
            gesture = { startDist: dist(evt.touches), startScale: zoom.scale, last: mid(evt.touches), moved: 0 };
            zoom.node.classList.add('pinching');
            evt.preventDefault();
          } else if (evt.touches.length == 1 && zoom.scale > zoom.base * 1.02) {
            // Already zoomed in: single finger pans (a plain tap still closes via click)
            gesture = { startDist: null, startScale: zoom.scale, last: { x: evt.touches[0].clientX, y: evt.touches[0].clientY }, moved: 0 };
            zoom.node.classList.add('pinching');
          }
        },
        { passive: false },
      );

      overlay.addEventListener(
        'touchmove',
        (evt) => {
          let zoom = this._overlayZoom;
          if (!zoom || !gesture) return;
          evt.preventDefault();
          let point;
          if (evt.touches.length == 2 && gesture.startDist != null) {
            zoom.scale = Math.min(Math.max((gesture.startScale * dist(evt.touches)) / gesture.startDist, zoom.base), 2.5 * zoom.base);
            point = mid(evt.touches);
          } else if (evt.touches.length == 1) {
            point = { x: evt.touches[0].clientX, y: evt.touches[0].clientY };
          } else {
            return;
          }
          gesture.moved += Math.abs(point.x - gesture.last.x) + Math.abs(point.y - gesture.last.y);
          zoom.tx += point.x - gesture.last.x;
          zoom.ty += point.y - gesture.last.y;
          gesture.last = point;
          zoom.node.style.transform = `translate(${zoom.tx}px, ${zoom.ty}px) scale(${zoom.scale})`;
        },
        { passive: false },
      );

      let endGesture = (evt) => {
        if (!gesture) return;
        let zoom = this._overlayZoom;
        if (evt.touches.length == 1 && zoom) {
          // Pinch released down to one finger: continue as a pan
          gesture = { startDist: null, startScale: zoom.scale, last: { x: evt.touches[0].clientX, y: evt.touches[0].clientY }, moved: gesture.moved };
          return;
        }
        if (evt.touches.length > 0) return;
        if (gesture.moved > 8) this._overlaySwallowClickUntil = Date.now() + 400;
        gesture = null;
        if (zoom) {
          zoom.node.classList.remove('pinching');
          if (zoom.scale < zoom.base * 1.02) {
            zoom.scale = zoom.base;
            zoom.tx = zoom.ty = 0;
            zoom.node.style.transform = `scale(${zoom.base})`;
          }
        }
      };
      overlay.addEventListener('touchend', endGesture);
      overlay.addEventListener('touchcancel', endGesture);
    },

    /**
     * Mobile uses a 1000px layout viewport, so the desktop overlay scale leaves the card at under
     * a third of the screen (users pinch-zoom to read it, which crashes iOS Safari):
     * scale it to fill ~90% of the visible screen instead
     */
    computeCardOverlayScale() {
      let scale = 100 / parseInt(this._cardScale);
      if (!dojo.hasClass('ebd-body', 'mobile_version')) return scale;
      let vv = window.visualViewport;
      let vw = vv ? vv.width : window.innerWidth;
      let vh = vv ? vv.height : window.innerHeight;
      // Displayed width = 235px intrinsic × (cardScale/100) [resizable] × overlay scale
      let targetW = Math.min(0.7 * vw, (0.85 * vh * 235) / 374);
      return Math.max(scale, (targetW / 235) * scale);
    },

    /**
     * Zoom on a card
     */
    zoomOnCard(cardId, config = {}) {
      let originalCard = $(cardId);
      // Card may not exist on this client (spectator / already destroyed by another notif)
      if (!originalCard) {
        return Promise.resolve();
      }
      this._zoomedCard = cardId;
      this.closeTooltip(cardId);
      let animatedCard = dojo.clone(originalCard);
      let scale =
        (parseFloat(this.getScale(originalCard.querySelector('.player-card-resizable'))) * 100) /
        parseInt(this._cardScale);

      dojo.addClass(originalCard, 'phantom'); // Make the original card invisible
      dojo.attr(animatedCard, 'id', cardId + '_animated'); // Add a prefix to avoid duplicate id
      dojo.style(animatedCard, 'transform', `scale(${scale})`);
      dojo.empty('card-overlay');
      dojo.place(animatedCard, 'card-overlay');

      animatedCard.querySelectorAll('.card-rulings-icon').forEach((n) => n.remove());
      let card =
        this.gamedatas.playerCards.find((c) => c.id === cardId) ||
        (this.gamedatas.players[this.player_id]?.hand || []).find((c) => c.id === cardId) ||
        null;
      if (card) {
        this.maybeAddCardRulingsIcon(animatedCard, card);
      }

      // Start animation
      config.from = config.from || originalCard;
      let anim = this.slide(animatedCard, 'card-overlay', config);
      dojo.addClass('card-overlay', 'active');
      let zoomScale = this.computeCardOverlayScale();
      dojo.style(animatedCard, 'transform', `scale(${zoomScale})`);
      this._overlayZoom = { node: animatedCard, base: zoomScale, scale: zoomScale, tx: 0, ty: 0 };
      dojo.removeClass(animatedCard, 'mini');

      // add card stats
      this.loadSaveCard(originalCard);

      // No stats panel on mobile: no room around the near-full-screen card
      if (dojo.hasClass('ebd-body', 'mobile_version') || originalCard.stats == null || Object.keys(originalCard.stats).length == 0) {
        return anim;
      }

      statsDesc = this.formatStats(originalCard.stats, originalCard);
      html = '<h3>' + _('Card Statistics') + '</h3> ' + statsDesc;
      dojo.place(html, dojo.query('.player-card-stats', animatedCard)[0]);
      dojo.query('.player-card-stats', animatedCard).addClass('active');
      return anim;
    },

    /**
     * Zoom off a card
     */
    zoomOffCard(config = {}) {
      if (this._zoomedCard == null) return;

      let cardId = this._zoomedCard;
      let originalCard = $(cardId);
      let animatedCard = $(cardId + '_animated');

      // originalCard may be gone from the DOM (e.g. card was destroyed by another notif).
      // Fall back to instant dismiss to avoid a TypeError leaving the overlay stuck.
      if (!originalCard) {
        return this.zoomOffCardInstant();
      }

      let scale =
        (parseFloat(this.getScale(originalCard.querySelector('.player-card-resizable'))) * 100) /
        parseInt(this._cardScale);

      config.destroy = true;
      let anim = this.slide(animatedCard, cardId, config).then(() => dojo.removeClass(originalCard, 'phantom'));
      dojo.removeClass('card-overlay', 'active');
      dojo.style(animatedCard, 'transform', `scale(${scale})`);
      dojo.addClass(animatedCard, 'mini');
      dojo.query('.player-card-stats', animatedCard).removeClass('active');
      this._zoomedCard = null;
      this._overlayZoom = null;
      return anim;
    },

    /**
     * Instantly dismiss the zoom overlay (no animation)
     */
    zoomOffCardInstant() {
      if (this._zoomedCard == null) {
        return Promise.resolve();
      }

      const cardId = this._zoomedCard;
      const originalCard = $(cardId);
      const animatedCard = $(cardId + '_animated');

      dojo.removeClass('card-overlay', 'active');
      dojo.empty('card-overlay');
      if (animatedCard) {
        dojo.destroy(animatedCard);
      }

      if (originalCard) {
        dojo.removeClass(originalCard, 'phantom');
      }

      this._zoomedCard = null;
      this._overlayZoom = null;
      return Promise.resolve();
    },

    /********************
     ******* DRAFT *******
     *********************/
    onEnteringStateDraftPlayers(args) {
      // Detect async vs legacy: async games have playerTurns in args or gamedatas
      const isAsync = !!(args.playerTurns || this.gamedatas.draftPlayerTurns);
      if (isAsync) {
        this._onEnteringAsyncDraft(args);
      } else {
        this._onEnteringLegacyDraft(args);
      }
    },

    /**
     * Legacy sync draft: exact main-branch behavior.
     * args._private is a flat array of card objects. Type/turn/draftChoice are top-level.
     */
    _onEnteringLegacyDraft(args) {
      if (this.isSpectator) return;

      this._isDraft = true;
      this.updateHandContainer();

      // Add cards and listeners
      dojo.query('#draft-container .player-card').forEach(dojo.destroy);
      let cards = args._private ? args._private : this.gamedatas.draft;
      cards.forEach((card) => {
        let cardId = card.id;
        if (!$(cardId)) {
          this.addCard(card);
          this.slideFromLeft(cardId);
        }
        if (!this.isReadOnly()) {
          this.onClick(cardId, () => this.onClickCardDraft(cardId));
        }
      });

      // Update action button
      this._draftType = args.type;
      this.updateDraftStatus();
    },

    /**
     * Async draft: per-player turns, progress bar, waiting UI, shadow cards.
     * args._private is an object with {cards, turn, type, draftChoice, lastPool}.
     */
    _onEnteringAsyncDraft(args) {
      // Initialise progress tracking — prefer live notifications, fall back to state args,
      // then gamedatas (page load). This ensures spectators and non-active players all see it.
      if (!this._draftPlayerTurns) {
        const source = args.playerTurns || this.gamedatas.draftPlayerTurns;
        if (source) {
          this._draftPlayerTurns = source;
          this._draftTotalTurns = args.total || this.gamedatas.draftTotalTurns;
        }
      }
      if (!this._draftFirstPlayer) {
        this._draftFirstPlayer = String(args.firstPlayer || this.gamedatas.draftFirstPlayer || '');
      }
      // Normalise keys to strings to match gamedatas.players key format.
      if (this._draftPlayerTurns) {
        const normalised = {};
        Object.entries(this._draftPlayerTurns).forEach(([k, v]) => { normalised[String(k)] = v; });
        this._draftPlayerTurns = normalised;
      }

      // Toggle draft (must be set before renderDraftProgressBar checks it).
      // Spectators also need _isDraft so that updateHandContainer() keeps
      // draft-wrapper active (e.g. after onScreenWidthChange).
      this._isDraft = true;

      // Render progress bar for everyone — spectators, active, and waiting players.
      if (this.isSpectator) {
        dojo.addClass('draft-wrapper', 'spectator');
      }
      this.updateHandContainer();
      this.renderDraftProgressBar();

      if (this.isSpectator) {
        const total = this._draftTotalTurns || args.total;
        if (total <= 1 && this.gamedatas.gamestate) {
          this.gamedatas.gamestate.description = _('Draft: players are choosing their cards');
          this.updatePageTitle();
        }
        return;
      }

      // Override per-player turn from private args (they're more up-to-date).
      if (args._private && args._private.turn) {
        if (!this._draftPlayerTurns) this._draftPlayerTurns = {};
        this._draftPlayerTurns[String(this.player_id)] = args._private.turn;
      }

      // If not active (e.g. page refresh while waiting), restore the waiting UI
      if (!this.isCurrentPlayerActive()) {
        if (this._draftPlayerTurns) {
          // Show unchosen cards from the last pack as inert shadow clones
          const lastPool = args._private ? (args._private.lastPool || []) : [];
          dojo.query('#draft-container .player-card').forEach(dojo.destroy);
          const container = $('draft-container');
          if (container) {
            lastPool.forEach((card) => {
              if (!$('shadow_' + card.id)) {
                container.appendChild(this._makeDraftShadowFromData(card));
              }
            });
          }
          const myTurn = this._draftPlayerTurns[String(this.player_id)];
          const draftComplete = this._draftTotalTurns && myTurn > this._draftTotalTurns;
          const predId = draftComplete ? null : this._getDraftPredecessorId();
          if (this.gamedatas.gamestate) {
            if (draftComplete) {
              this.gamedatas.gamestate.description = _('Draft: waiting for other players to finish');
            } else {
              const predecessor = predId ? this.gamedatas.players[predId] : null;
              if (predecessor) {
                this.gamedatas.gamestate.description = _('Draft: waiting for next pack from')
                  + ` <strong style="color:#${predecessor.color}">${predecessor.name}</strong>`;
              }
            }
            this.updatePageTitle();
          }
        }
        return;
      }

      // Add cards and listeners
      dojo.query('#draft-container .player-card').forEach(dojo.destroy);
      let cards = (args._private && args._private.cards && args._private.cards.length > 0) ? args._private.cards : (this.gamedatas.draft || []);
      cards.forEach((card) => {
        let cardId = card.id;
        const alreadyInDom = !!$(cardId);
        if (!alreadyInDom) {
          this.addCard(card);
          this.slideFromLeft(cardId);
        }

        if (!this.isReadOnly()) {
          this.onClick(cardId, () => this.onClickCardDraft(cardId));
        }
      });

      // Update action button — use this player's own type from private args
      this._draftType = args._private ? args._private.type : args.type;
      this.updateDraftStatus();
      // Fix the title bar: override turn and draftChoice with this player's private args
      if (args._private && this.gamedatas.gamestate && this.gamedatas.gamestate.args) {
        if (args._private.turn) this.gamedatas.gamestate.args.turn = args._private.turn;
        if (args._private.draftChoice) this.gamedatas.gamestate.args.draftChoice = args._private.draftChoice;
        // One-shot: strip round counter and "Draft:" prefix
        const total = this._draftTotalTurns || args.total;
        if (total <= 1) {
          this.gamedatas.gamestate.descriptionmyturn = this.gamedatas.gamestate.descriptionmyturn
            .replace('(${turn}/${total}) ', '').replace('Draft: ', '');
        }
        this.updatePageTitle();
      }
    },

    renderDraftProgressBar() {
      this.renderDraftProgress();
    },

    renderDraftProgress() {
      const existing = $('agr-draft-progress-row');
      if (existing) existing.remove();
      const wrapper = $('draft-wrapper');
      if (!wrapper || !this._draftPlayerTurns
          || Object.keys(this._draftPlayerTurns).length === 0
          || !this._draftTotalTurns || this._draftTotalTurns <= 1) return;
      const row = document.createElement('div');
      row.id = 'agr-draft-progress-row';
      row.innerHTML = this._buildDraftProgressHTML();
      wrapper.insertBefore(row, wrapper.firstChild);
    },

    _buildDraftProgressHTML() {
      const total = this._draftTotalTurns;
      const turns = this._draftPlayerTurns;
      const myId = String(this.player_id);
      const fpId = this._draftFirstPlayer || '';
      const playerCount = Object.keys(this.gamedatas.players).length;
      // Pivot player appears first; remaining follow in turn order from that seat.
      // Non-spectators pivot on themselves; spectators pivot on the starting player.
      const fpNo = fpId && this.gamedatas.players[fpId] ? this.gamedatas.players[fpId].no : 1;
      const myNo = this.gamedatas.players[myId] ? this.gamedatas.players[myId].no : 0;
      const pivotNo = this.isSpectator ? fpNo : myNo;
      const players = Object.entries(this.gamedatas.players).sort(([aId, a], [bId, b]) => {
        const aOrd = ((a.no - pivotNo + playerCount) % playerCount);
        const bOrd = ((b.no - pivotNo + playerCount) % playerCount);
        return aOrd - bOrd;
      });

      let chips = '';
      players.forEach(([pid, p]) => {
        const t = turns[pid] ?? 1;
        const done = Math.max(0, t - 1);
        let pips = '';
        for (let i = 1; i <= total; i++) {
          pips += `<span class="dp-pip${i < t ? ' done' : ''}"></span>`;
        }
        chips += `<div class="dp-chip">
          <span class="dp-chip-name" style="color:#${p.color}">${p.name}</span>
          <div class="dp-chip-pips">${pips}</div>
          <span class="dp-chip-count">${done}/${total}</span>
        </div>`;
      });

      return `<div class="dp-module" data-players="${playerCount}">
        <span class="dp-title">Draft progress</span>
        <div class="dp-chips">${chips}</div>
      </div>`;
    },

    /**
     * Convert a pool card element into an inert visual shadow:
     * clones the node (stripping event listeners), gives it a shadow_ prefix ID,
     * adds unselectable styling, and swallows all pointer events.
     */
    _makeDraftShadowFromEl(cardEl) {
      const clone = cardEl.cloneNode(true);
      clone.id = 'shadow_' + cardEl.id;
      clone.classList.add('unselectable', 'draft-pool-shadow');
      const swallow = (e) => { e.preventDefault(); e.stopPropagation(); e.stopImmediatePropagation(); };
      ['click', 'mousedown', 'mouseup', 'pointerdown', 'pointerup', 'touchstart', 'touchend']
        .forEach((t) => clone.addEventListener(t, swallow, true));
      return clone;
    },

    /**
     * Build an inert shadow card from raw card data (used on page refresh
     * when the original element no longer exists in the DOM).
     */
    _makeDraftShadowFromData(card) {
      const tmp = document.createElement('div');
      tmp.innerHTML = this.tplPlayerCard(Object.assign({}, card, { mini: false }), false);
      const node = tmp.firstElementChild;
      node.id = 'shadow_' + card.id;
      node.classList.add('unselectable', 'draft-pool-shadow');
      const swallow = (e) => { e.preventDefault(); e.stopPropagation(); e.stopImmediatePropagation(); };
      ['click', 'mousedown', 'mouseup', 'pointerdown', 'pointerup', 'touchstart', 'touchend']
        .forEach((t) => node.addEventListener(t, swallow, true));
      return node;
    },

    /**
     * Compute the predecessor player ID in draft order (the player who passes to us).
     * Players pass in seat order; predecessor is the player before us in that order.
     */
    _getDraftPredecessorId() {
      const players = Object.entries(this.gamedatas.players).sort(([, a], [, b]) => a.no - b.no);
      const myIndex = players.findIndex(([pid]) => pid === String(this.player_id));
      if (myIndex < 0) return null;
      return players[(myIndex - 1 + players.length) % players.length][0];
    },

    getDraftSelection() {
      // Count distinct cards: slide() phantom clones keep the pending class mid-animation
      let count = (type) =>
        new Set([...document.querySelectorAll('.player-card.' + type + '.pending')].map((el) => el.getAttribute('data-id')))
          .size;
      return {
        occupation: count('occupation'),
        minor: count('minor'),
      };
    },

    onClickCardDraft(cardId) {
      if (dojo.hasClass(cardId, 'unselectable')) {
        return;
      }

      if (dojo.hasClass(cardId, 'pending')) {
        this.takeAction('actDraftRemove', { cardId }, false);
      } else {
        this.takeAction('actDraftAdd', { cardId });
      }
    },

    notif_addCardToDraftSelection(n) {
      debug('Notif: add card to draft selection', n);
      let cardId = n.args.cardId;
      dojo.addClass(cardId, 'pending');
      dojo.attr(cardId, 'data-state', n.args.pos);
      this.updateDraftStatus();

      // Compute position using state data-attr
      let cards = [...$('hand-container').querySelectorAll('.player-card')];
      let brother = cards.reduce(
        (carry, card) => carry || (card.getAttribute('data-state') > n.args.pos ? card : carry),
        null,
      );
      // Slide it, then recompute status: a status update that ran mid-slide saw clone-inflated counts
      this.slide(cardId, 'hand-container', {
        phantom: true,
        beforeBrother: brother,
      }).then(() => this.updateDraftStatus());
      if (!this.isFastMode()) {
        sortable('#hand-container');
      }
    },
    notif_removeCardFromDraftSelection(n) {
      debug('Notif: remove card to draft selection', n);
      let cardId = n.args.cardId;
      dojo.removeClass(cardId, 'pending');
      this.updateDraftStatus();
      this.slide(cardId, 'draft-container', { phantom: true }).then(() => this.updateDraftStatus());
    },

    updateDraftStatus() {
      // Can fire from slide callbacks after the draft UI is gone
      if (!this._draftType || !$('draft-container')) {
        return;
      }
      let selection = this.getDraftSelection();
      let allSet = true;

      // Check minors (exclude shadow cards — they keep unselectable permanently)
      if (selection.minor == this._draftType.minor) {
        dojo.query('#draft-container .player-card.minor:not(.pending):not(.draft-pool-shadow)').addClass('unselectable');
      } else {
        dojo.query('#draft-container .player-card.minor:not(.pending):not(.draft-pool-shadow)').removeClass('unselectable');
        allSet = false;
      }

      // Check occupations
      if (selection.occupation == this._draftType.occupation) {
        dojo.query('#draft-container .player-card.occupation:not(.pending):not(.draft-pool-shadow)').addClass('unselectable');
      } else {
        dojo.query('#draft-container .player-card.occupation:not(.pending):not(.draft-pool-shadow)').removeClass('unselectable');
        allSet = false;
      }

      dojo.destroy('btnConfirmDraft');
      if (allSet && this.isCurrentPlayerActive()) {
        this.addPrimaryActionButton('btnConfirmDraft', _('Confirm selection'), () =>
          this.takeAction('actDraftConfirm'),
        );
      }
    },

    onUpdateActivityDraftPlayers(_args, _stats) {
      if (this._draftType != null) this.updateDraftStatus();
    },

    notif_confirmDraftSelection(n) {
      debug('Notif: confirming draft selection', n);
      dojo.removeClass(n.args.card.id, 'pending');
    },
    notif_clearDraftPools(n) {
      debug('Notif: clearing draft pools', n);
      const cards = dojo.query('#draft-container .player-card');
      // If the container is already empty (e.g. auto-pick never showed cards), do nothing.
      // Don't cancel any pending timeout — a previous clearDraftPools may still be mid-animation.
      if (cards.length === 0) return;
      cards.forEach((oCard) => this.slideToRight(oCard));
      // Use a long timeout so notif_newDraftPile can cancel it if new cards arrive
      // before old ones are destroyed (prevents a brief container-collapse glitch).
      if (this._clearDraftTimeout) clearTimeout(this._clearDraftTimeout);
      let destroyDelay = this.isInstantSpeed() ? 10 : 2000;
      this._clearDraftTimeout = setTimeout(() => {
        cards.forEach(dojo.destroy);
        this._clearDraftTimeout = null;
      }, destroyDelay);
    },

    notif_newDraftPile(n) {
      debug('Notif: new draft pile', n);
      // Cancel any pending destroy from notif_clearDraftPools (sync draft path)
      if (this._clearDraftTimeout) {
        clearTimeout(this._clearDraftTimeout);
        this._clearDraftTimeout = null;
      }

      const addNewCards = () => {
        (n.args.cards || []).forEach((card) => {
          const cardId = card.id;
          if (!$(cardId)) {
            this.addCard(card);
            this.slideFromLeft(cardId);
          }
          if (!this.isReadOnly()) {
            this.onClick(cardId, () => this.onClickCardDraft(cardId));
          }
        });
        this._draftType = n.args.type;
        this.updateDraftStatus();
        if (this.gamedatas.gamestate && this.gamedatas.gamestate.args) {
          this.gamedatas.gamestate.args.turn = n.args.turn;
          if (n.args.draftChoice) this.gamedatas.gamestate.args.draftChoice = n.args.draftChoice;
          this.updatePageTitle();
        }
      };

      const oldCards = dojo.query('#draft-container .player-card');
      if (!this.isFastMode() && !this.isInstantSpeed() && oldCards.length > 0) {
        oldCards.forEach((oCard) => this.slideToRight(oCard));
        setTimeout(() => {
          dojo.query('#draft-container .player-card').forEach(dojo.destroy);
          addNewCards();
        }, Math.round(500 * this.getAnimationSpeedMultiplier()));
      } else {
        dojo.query('#draft-container .player-card').forEach(dojo.destroy);
        addNewCards();
      }
    },

    notif_draftProgress(n) {
      debug('Notif: draft progress', n);
      // Normalise keys to strings to match gamedatas.players key format
      const normalised = {};
      Object.entries(n.args.allTurns).forEach(([k, v]) => { normalised[String(k)] = v; });
      this._draftPlayerTurns = normalised;
      this._draftTotalTurns = n.args.total;
      // Re-render the progress bar (visible to everyone during draft)
      this.renderDraftProgressBar();
      // Keep the spectator's banner turn counter up to date
      if (this.isSpectator && this.gamedatas.gamestate && this.gamedatas.gamestate.args) {
        const minTurn = Math.min(...Object.values(normalised));
        this.gamedatas.gamestate.args.turn = minTurn;
        this.updatePageTitle();
      }
    },

    notif_draftWaiting(n) {
      debug('Notif: draft waiting', n);
      // Normalise keys to strings to match gamedatas.players key format
      const normalised = {};
      Object.entries(n.args.allTurns).forEach(([k, v]) => { normalised[String(k)] = v; });
      this._draftPlayerTurns = normalised;
      this._draftTotalTurns = n.args.total;
      // Replace remaining pool cards with inert shadow clones (no event handlers)
      const container = $('draft-container');
      dojo.query('#draft-container .player-card').forEach((cardEl) => {
        const shadow = this._makeDraftShadowFromEl(cardEl);
        container.appendChild(shadow);
        dojo.destroy(cardEl);
      });
      this.renderDraftProgressBar();
      // Update the title bar and waiting text
      if (this.gamedatas.gamestate) {
        const myTurnAfter = normalised[String(this.player_id)];
        const draftComplete = myTurnAfter > n.args.total;
        if (draftComplete) {
          this.gamedatas.gamestate.description = _('Draft: waiting for other players to finish');
        } else {
          const predecessor = n.args.predecessorId ? this.gamedatas.players[String(n.args.predecessorId)] : null;
          if (predecessor) {
            this.gamedatas.gamestate.description = _('Draft: waiting for next pack from')
              + ` <strong style="color:#${predecessor.color}">${predecessor.name}</strong>`;
          }
        }
        this.updatePageTitle();
      }
    },

    notif_draftIsOver(n) {
      debug('Notif: draft is over');

      dojo.query('#draft-container .player-card').forEach(dojo.destroy);

      this._isDraft = false;
      this._draftPlayerTurns = null;
      this._draftTotalTurns = null;
      this._draftFirstPlayer = null;
      const row = $('agr-draft-progress-row');
      if (row) row.remove();
      dojo.removeClass('draft-wrapper', 'spectator');
      this.updateHandContainer();
    },

    slideFromLeft(elem) {
      if (this.isFastMode() || this.isInstantSpeed()) return;
      elem = typeof elem == 'string' ? $(elem) : elem;
      let x = elem.offsetWidth + elem.offsetLeft + 30;
      dojo.addClass(elem, 'notransition');
      dojo.style(elem, 'opacity', '0');
      dojo.style(elem, 'left', -x + 'px');
      elem.offsetHeight;
      dojo.removeClass(elem, 'notransition');

      dojo.style(elem, 'opacity', '1');
      dojo.style(elem, 'left', '0px');
    },

    slideToRight(elem) {
      if (this.isFastMode() || this.isInstantSpeed()) return;
      elem = typeof elem == 'string' ? $(elem) : elem;
      let stack = elem.parentNode;
      let x = stack.offsetWidth - elem.offsetLeft + 100;
      dojo.style(elem, 'left', x + 'px');
    },

    /*********************************
    ******* LIVING HAND REFILL *******
    *********************************/

    setLivingHandDraftMode(isOn) {
      if (isOn) {
        if (this.isSpectator) return;
        if (!this.isCurrentPlayerActive()) return;
      }
      
      if (this._isDraft === isOn) return;
      this._isDraft = isOn;
      this.updateHandContainer();
    },

    getLivingHandOfferFromArgs(args) {
      if (!args) return [];
      if (args._private && args._private.offer) return args._private.offer;
      if (args.offer) return args.offer;
      return [];
    },

    renderLivingHandOffer(offerCards, animate = true) {
      dojo.query('#draft-container .player-card').forEach(dojo.destroy);
      dojo.destroy('btnConfirmDraft');

      (offerCards || []).forEach((card) => {
        const cardId = '' + card.id;

        if (!$(cardId)) {
          this.addCard(card);
        }

        const el = $(cardId);
        if (!el) return;

        if (animate) {
          dojo.addClass(el, 'notransition');
          dojo.style(el, 'opacity', '0');
        }

        dojo.place(el, 'draft-container');

        if (animate) {
          el.offsetHeight; // force reflow
          dojo.removeClass(el, 'notransition');
          dojo.style(el, 'opacity', '1');
        }
      });

      this.enableLivingHandOfferClicks();
    },

    enableLivingHandOfferClicks() {
      if (this.isReadOnly()) return;

      dojo.query('#draft-container .player-card').forEach((node) => {
        const id = node && node.id;
        if (!id) return;

        if (dojo.hasClass(id, 'selectable')) return;

        this.onClick(id, () => this.onClickCardLivingHand(id));
      });
    },

    onEnteringStateLivingHandRefill(args) {
      if (this.isSpectator) return;
      if (!this.isCurrentPlayerActive()) return;
      this.setLivingHandDraftMode(true);

      const offer = this.getLivingHandOfferFromArgs(args);
      if (offer && offer.length) {
        const offerIds = offer.map((c) => '' + c.id);
        const shownIds = dojo.query('#draft-container .player-card').map((n) => n.id);

        const same =
          shownIds.length === offerIds.length &&
          offerIds.every((id) => shownIds.includes(id));

        if (!same) {
          this.renderLivingHandOffer(offer, false);
          return;
        }
      }

      this.enableLivingHandOfferClicks();
      dojo.destroy('btnConfirmDraft');
    },

    onLeavingStateLivingHandRefill() {
      dojo.query('#draft-container .player-card').forEach(dojo.destroy);
      dojo.destroy('btnConfirmDraft');

      this.setLivingHandDraftMode(false);
    },

    onClickCardLivingHand(cardId) {
      if (this.isReadOnly()) return;
      if (!$(cardId)) return;
      if (dojo.hasClass(cardId, 'unselectable')) return;

      // Anti double-click until server responds
      dojo.addClass(cardId, 'unselectable');
      dojo.removeClass(cardId, 'selectable');

      this.takeAction('actLivingHandPick', { cardId });
    },

    onEnteringStateLivingHandPassingDecision(args) {
      if (this.isSpectator) return;
      if (!this.isCurrentPlayerActive()) return;

      this.setLivingHandDraftMode(false);

      let card = null;
      if (!args) {
        card = null;
      } else if (args._private && args._private.card) {
        card = args._private.card;
      } else if (args.card) {
        card = args.card;
      } else {
        card = null;
      }
      let cardName = _('this card');
      if (card && card.name) {
        cardName = _(card.name);
      } else if (args && args.card_name) {
        cardName = _(args.card_name);
      }

      this.addPrimaryActionButton(
        'btnLivingHandPassKeep',
        dojo.string.substitute(_('Keep ${card_name}'), { card_name: cardName }),
        () => this.takeAction('actLivingHandPassDecision', { decision: 'keep' }),
      );
      this.addSecondaryActionButton(
        'btnLivingHandPassDecline',
        dojo.string.substitute(_('Decline ${card_name}'), { card_name: cardName }),
        () => this.takeAction('actLivingHandPassDecision', { decision: 'decline' }),
      );

      if (this.prefs[HAND_CARDS].value == 0) {
        this.openHandModal();
      }
    },

    notif_livingHandOfferCreated(n) {
      debug('Notif: living hand offer created', n);
      if (!this.isCurrentPlayerActive()) return;

      this.setLivingHandDraftMode(true);

      const offer = (n.args && n.args.offer) ? n.args.offer : [];
      this.renderLivingHandOffer(offer, true);
    },

    notif_livingHandPicked(n) {
      debug('Notif: living hand picked', n);

      const cardId = n.args.cardId;
      if (!$(cardId)) return;

      dojo.addClass(cardId, 'unselectable');
      dojo.removeClass(cardId, 'selectable');

      let cards = [...$('hand-container').querySelectorAll('.player-card')];
      let brother = cards.reduce(
        (carry, card) => carry || (card.getAttribute('data-state') > n.args.pos ? card : carry),
        null,
      );

      this.slide(cardId, 'hand-container', { destroy: true, beforeBrother: brother });
    },

    notif_livingHandOfferCleared(n) {
      debug('Notif: living hand offer cleared', n);

      dojo.query('#draft-container .player-card').forEach(dojo.destroy);

      if (n.args && n.args.done) {
        this.setLivingHandDraftMode(false);
      }
    },

  });
});
