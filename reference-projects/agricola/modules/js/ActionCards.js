define(['dojo', 'dojo/_base/declare'], (dojo, declare) => {
  const PLAYER_ACTION_CARDS = ['C162_ForestOwner', 'C104_Collector', 'D23_PioneeringSpirit', 'B42_ForestInn', 'C39_StudioBoat', 'D116_TreeInspector','D127_HardworkingMan','A162_ForestTallyman','A39_Chapel', 'E81_AlchemistsLab', 'E161_ElderBaker', 'D51_Archway', 'C22_BasketChair'];
  return declare('agricola.actionCards', null, {
    /**
     * Setup all actions slot/cards
     */
    setupActionCards() {
      for (var i = 1; i <= 14; i++) {
        this.place('tplTurnContainer', i, 'central-board');
      }
      
      this.gamedatas.cards.visible.forEach((card) => {
        this.addActionCard(card);
      });
      this.setupActionCardsHelpers();
    },

    setupActionCardsHelpers() {
      // Tooltip on future rounds
      this.gamedatas.cards.help.forEach((card) => {
        card.description = this.formatCardDesc(card.desc);
        card.tooltipText = '';
        card.tooltipDescription = this.formatCardDesc(card.tooltipDesc);
      });
      this.updateActionCardsHelp();
    },

    updateActionCardsHelp() {
      let bounds = [1, 5, 8, 10, 12, 14, 15];
      for (var i = 0; i < bounds.length - 1; i++) {
        let lower = bounds[i],
          upper = bounds[i + 1];

        let html =
          '<div class="action-card-help-tooltip">' +
          this.gamedatas.cards.help.reduce((html, card) => {
            if ($(card.id)) return html;

            let turn = card.location.split('_')[1];
            return html + (lower <= turn && turn < upper ? this.tplActionCardTooltip(card) : '');
          }, '') +
          '</div>';

        for (var j = lower; j < upper; j++) {
          if (!$('turn_' + j)) {
            continue;
          }
          this.addTooltipHtml('turn-number-tooltipable-' + j, html);
        }
      }
    },

    /**
     * Main factory function for action card
     */
    addActionCard(card, flip = false) {
      // Add extra info
      card.description = this.formatCardDesc(card.desc);
      card.tooltipText = card.tooltip.map((s) => _(s)).join('<br />');
      card.tooltipDescription = this.formatCardDesc(card.tooltipDesc);

      // Place it
      let addTooltip = () => {
        let method = card.component? "tplActionComponentTooltip"  : "tplActionCardTooltip";
        this.addCustomTooltip(card.id, this[method](card));
      };

      if (card.component) {
        this.place('tplActionCard', card, card.container + '-board');
        addTooltip();
      } else {
        if (PLAYER_ACTION_CARDS.includes(card.id)) {
          return;
        }

        if (flip) {
          let target = $(card.location).querySelector('.turn-number');
          if (this.isFastMode() || this.isInstantSpeed()) {
            this.flipAndReplace(target, this.tplActionCard(card));
            addTooltip();
          } else {
            this.flipAndReplace(target, this.tplActionCard(card)).then(addTooltip);
          }
        } else {
          let target = $(card.location).querySelector('.turn-number');
          dojo.destroy(target); // Remove turn number slot
          this.place('tplActionCard', card, card.location);
          addTooltip();
        }
      }
    },

    /**
     * Format action card desc
     */
    formatCardDesc(desc) {
      if (!desc) {
        return '';
      }

      return desc
        .map((s) => _(s))
        .map((s) => s.replace(/__([^_]+)__/g, '<span class="action-card-name-reference">$1</span>'))
        .map((s) => '<div>' + this.formatStringMeeples(s) + '</div>')
        .join('');
    },

    /**
     * When a new action card is revealed at the begining of a turn
     */
    notif_revealActionCard(n) {
      debug('Notif: revealing a new card', n);
      this.addActionCard(n.args.card, true);
      dojo.attr('game_play_area', 'data-turn', n.args.turn);
      this.updateActionCardsHelp();
    },

    /**
     * Slot for turn
     */
    tplTurnContainer(turn) {
      return (
        `
      <div class='turn-action-container' id='turn_${turn}'>
        <div class='turn-number'>
          <div class='help-marker' id='turn-number-tooltipable-${turn}'>
            <svg><use href="#help-marker-svg" /></svg>
          </div>
          <div class='turn-number-indication'>${turn}</div>
        </div>
        <div class='future-resources-holder'>
        ` +
        Object.values(this.gamedatas.players)
          .map(
            (player) =>
              `<div data-pid="${player.id}" data-color="${player.color}" class='future-resource-wrapper'>
                <div class='resource-holder-update'></div>
                <div class='future-resource-description'></div>
              </div>`,
          )
          .join('') +
        `
        </div>
      </div>
      `
      );
    },

    /**
     * Template for action card
     */
    tplActionCard(card) {
      let accumulate = card.accumulate != '' ? 'accumulate-' + card.accumulate : '';
      let component = card.component ? 'component' : '';
      return (
        `
      <div id="${card.id}" data-id="${card.id}" class="action-card-holder ${component}">
        <div class="farmer-holder resource-holder-update" data-n="0"></div>
        <div class="action-card ${accumulate} action-${card.size}">
          <h4 class="action-header">${_(card.name)}</h4>
          <div class="action-desc">${card.description}</div>
          <div class="action-footer"></div>
        </div>
        ` +
        (card.accumulate != '' ? "<div class='resource-holder resource-holder-update'></div>" : '') +
        `
      </div>
      `
      );
    },

    /**
     * Template for tooltip
     */
    tplActionComponentTooltip(card) {
      let accumulate = card.accumulate != '' ? 'accumulate-' + card.accumulate : '';
      return `
          <div class="action-component-tooltip">
            <div class="action-holder" data-id="${card.id}">
              <div id="${card.id}" data-id="${card.id}" class="action-card-holder component">
                <div class="action-card ${accumulate} action-${card.size}">
                  <h4 class="action-header">${_(card.name)}</h4>
                  <div class="action-desc">${card.description}</div>
                  <div class="action-footer"></div>
                </div>
              </div>
            </div>
            ${card.tooltipText}
          </div>
          `;
    },

    /**
     * Template for action card tooltip
     */
    tplActionCardTooltip(card) {
      return `
      <div class="action-card-tooltip">
        <div class="action-holder" data-id="${card.id}">
          <div data-id="${card.id}" class="action-card-holder">
            <div class="action-card">
              <h4 class="action-header">${_(card.name)}</h4>
              <div class="action-desc">${card.tooltipDescription}</div>
            </div>
          </div>
        </div>
        <div class="card-tooltip-text">
          ${card.tooltipText}
        </div>
      </div>
      `;
    },

    getAdditionalBoardActionSpaceIds() {
      return Array.from(document.querySelectorAll('#add-board .action-card-holder[id]')).map((node) => node.id);
    },

    hasFarmerOnActionSpace(cId) {
      const node = $(cId);
      if (!node) {
        return false;
      }
      const holder = node.querySelector('.farmer-holder');
      const n = holder == null ? 0 : parseInt(holder.getAttribute('data-n') || '0', 10);
      return n > 0;
    },

    updateAdditionalActionLockUI() {
      const addBoard = $('add-board');
      if (!addBoard) {
        return false;
      }

      const additionalSpaceIds = this.getAdditionalBoardActionSpaceIds();
      const locked = additionalSpaceIds.some((cId) => this.hasFarmerOnActionSpace(cId));
      dojo.toggleClass(addBoard, 'aas-locked', locked);

      if (!locked) {
        additionalSpaceIds.forEach((cId) => {
          dojo.removeClass(cId, 'aas-blocked-sibling');
          const node = $(cId);
          if (node) {
            node.removeAttribute('title');
          }
        });
      }

      return locked;
    },

    decorateBlockedAdditionalSiblings(cards, allCards) {
      const selectable = new Set(Array.isArray(cards) ? cards : Object.values(cards || {}));
      const visible = new Set(Array.isArray(allCards) ? allCards : Object.values(allCards || {}));
      const additionalSpaceIds = this.getAdditionalBoardActionSpaceIds().filter((cId) => visible.has(cId));

      if (additionalSpaceIds.length === 0) {
        return;
      }

      const groupLocked = additionalSpaceIds.some((cId) => this.hasFarmerOnActionSpace(cId));
      const blockedSiblings = new Set(
        additionalSpaceIds.filter((cId) => !selectable.has(cId) && !this.hasFarmerOnActionSpace(cId) && groupLocked),
      );

      const reason = _(
        'This action is unavailable because another action in the additional action spaces area was already taken this round.',
      );

      additionalSpaceIds.forEach((cId) => {
        const node = $(cId);
        if (!node) {
          return;
        }
        node.removeAttribute('title');

        if (!blockedSiblings.has(cId)) {
          dojo.removeClass(cId, 'aas-blocked-sibling');
          return;
        }

        dojo.addClass(cId, 'aas-blocked-sibling');

        const onBlockedSpaceInteraction = (evt) => {
          if (this.isInterfaceLocked() || this._helpMode) {
            return false;
          }

          const now = Date.now();
          if (this._lastAasBlockedReasonAt != null && now - this._lastAasBlockedReasonAt < 450) {
            return false;
          }
          this._lastAasBlockedReasonAt = now;

          if (evt && evt.preventDefault) {
            evt.preventDefault();
          }
          if (evt && evt.stopPropagation) {
            evt.stopPropagation();
          }
          this.showMessage(reason, 'info');
          return false;
        };

        this.connect(cId, 'click', onBlockedSpaceInteraction);
        this.connect(cId, 'touchend', onBlockedSpaceInteraction);
      });
    },

    /**
     * Make cards selectable
     */
    promptActionCard(cards, callback, allCards = []) {
      if (this.isFastMode()) return;

      // Coerce PHP associative arrays (JSON objects) into JS arrays; prevents Sheep Inspector bug when array structure is lost
      if (!Array.isArray(allCards)) {
        allCards = Object.values(allCards || {});
      }
      if (!Array.isArray(cards)) {
        cards = Object.values(cards || {});
      }

      this.updateAdditionalActionLockUI();
      allCards.forEach((cId) => dojo.addClass(cId, 'unselectable'));
      this.decorateBlockedAdditionalSiblings(cards, allCards);
      cards.forEach((cId) => {
        this.onClick(cId, () => callback(cId));
      });
    },

    promptActionCardMultiple(cards, min, max, callback, callbackCheck = null) {
      let selectedCards = [];
      let select = (cId) => {
        dojo.addClass(cId, 'selected');
        ['.action-header', '.action-desc', '.action-footer'].forEach((element) => dojo.query(`[data-id="${cId}"] ${element}`).removeClass('prompted'));
        ['.action-header', '.action-desc', '.action-footer'].forEach((element) => dojo.query(`[data-id="${cId}"] ${element}`).addClass('selected'));
      };
      let unselect = (cId) => {
        dojo.removeClass(cId, 'selected');
        ['.action-header', '.action-desc', '.action-footer'].forEach((element) => dojo.query(`[data-id="${cId}"] ${element}`).addClass('prompted'));
        ['.action-header', '.action-desc', '.action-footer'].forEach((element) => dojo.query(`[data-id="${cId}"] ${element}`).removeClass('selected'));
      };

      let clear = (cId) => {
        ['.action-header', '.action-desc', '.action-footer'].forEach((element) => dojo.query(`[data-id="${cId}"] ${element}`).removeClass('prompted'));
        ['.action-header', '.action-desc', '.action-footer'].forEach((element) => dojo.query(`[data-id="${cId}"] ${element}`).removeClass('selected'));
      };

      let onClickCard = (cId) => {
        if (min == 1 && max == null) {
          callback(cId);
          return;
        }
        
        // Toggle element
        let i = selectedCards.findIndex((p) => p == cId);
        if (i == -1) {
          select(cId);
          selectedCards.push(cId);
        } else {
          unselect(cId);
          selectedCards.splice(i, 1);
        }

        // Add clear button
        dojo.destroy('btnClearZoneSelection');
        dojo.destroy('btnConfirmZoneSelection');
        if (selectedCards.length > 0) {
          this.addSecondaryActionButton('btnClearZoneSelection', _('Clear'), () => {
            selectedCards.forEach(unselect);
            selectedCards = [];
            dojo.destroy('btnClearZoneSelection');
            dojo.destroy('btnConfirmZoneSelection');
          });
        }

        // Add confirm button
        if (
          selectedCards.length >= min &&
          selectedCards.length <= max &&
          (callbackCheck == null || callbackCheck(selectedCards))
        ) {
          this.addPrimaryActionButton('btnConfirmZoneSelection', _('Confirm'), () => {
            cards.forEach(clear);
            callback(selectedCards);
          });
        }
      };

      // Attach event to cards
      cards.forEach((cId) => {
        ['.action-header', '.action-desc', '.action-footer'].forEach((element) => dojo.query(`[data-id="${cId}"] ${element}`).addClass('prompted'));
        this.onClick(cId, () => onClickCard(cId));
      });
    },
  });
});
