define(['dojo', 'dojo/_base/declare'], (dojo, declare) => {
  const RESOURCES = ['wood', 'clay', 'reed', 'stone', 'grain', 'vegetable', 'food', 'sheep', 'pig', 'cattle'];
  const ANIMALS = ['sheep', 'pig', 'cattle'];
  const DISCARDABLE = ['wood', 'clay', 'reed', 'stone', 'food', 'sheep', 'pig', 'cattle'];
  const BREAD = 2;
  return declare('agricola.exchange', null, {
    /**
     * Entering the exchange state : create modal and init selection
     */
    onEnteringStateExchange(args) {
      this._possibleExchanges = args.exchanges;
      this._originalReserve = args.resources;
      this._currentExchanges = []; // Stack of exchanges we want to make
      this._exchangeTrigger = args.trigger;
      // this._harvestMinFood = args.harvestMinFood;
      this._extraAnimals = args.extraAnimals;
      this._mandatoryExchange = args.mandatory;

      if (!args.automaticAction) {
        // Setup and display dialog
        this.addPrimaryActionButton('btnShowPossibleExchanges', _('Show possible exchanges'), () =>
          this.openExchangeModal(),
        );
        this.setupExchangeModal();
      }
    },

    /**
     * Create exchange modal along with counters and buttons
     */
    setupExchangeModal() {
      if (this._exchangeDialog) {
        this._exchangeDialog.kill();
      }

      // Create modal
      this._exchangeDialog = new customgame.modal('showExchanges', {
        autoShow: true,
        class: 'agricola_popin',
        closeIcon: 'fa-times',
        title: _('Exchange center'),
        closeAction: 'hide',
        verticalAlign: 'flex-start',
        contents: `
          <div id="exchanges-container">
            <div id="exchanges-grid"></div>
          </div>
          <div id="exchanges-discard-header"></div>
          <div id="exchanges-discard"></div>
          <div id="exchanges-reserve"></div>
          <div id="exchanges-dialog-footer"></div>
        `,
      });

      // Setup counters
      this._exchangesCounters = {};
      RESOURCES.forEach((res) => {
        dojo.place(this.tplResourceCounter({ id: this.player_id }, res, 'exchange_'), 'exchanges-reserve');
        this._exchangesCounters[res] = new ebg.counter();
        this._exchangesCounters[res].create('exchange_resource_' + this.player_id + '_' + res);
      });
      dojo.place('<span id="exchanges-score-indicator"></span>', 'exchanges-reserve');

      // Create rows
      this._possibleExchanges.forEach((exchange, i) => {
        exchange.id = i;
        this.place('tplExchangeRow', exchange, 'exchanges-grid');
        this.connect('exchange-' + i, 'click', () => this.onClickExchange(i));
      });

      // Create discard menu
      if (this._exchangeTrigger != BREAD && this._exchangeTrigger != 'huntingTrophy') {
        dojo.place('<h3> <b>' + _('Discard goods') + '</b> </h3>', 'exchanges-discard-header');
        DISCARDABLE.forEach((resource) => {
          this.addDangerActionButton(
            'discard-' + resource,
            this.formatStringMeeples('<' + resource.toUpperCase() + '>'),
            () => this.onClickDiscard(resource),
            'exchanges-discard',
          );
        });
      }

      /* Is it harvest ??
      if (this._harvestMinFood != 0) {
        dojo.place(
          '<h3>' +
            _('Food needed for your family:') +
            ' ' +
            this._harvestMinFood +
            this.formatStringMeeples('<FOOD>') +
            '</h3>',
          'exchanges-container',
          'before',
        );
      }
      */

      // Has extra animals ?
      if (this._extraAnimals.sheep + this._extraAnimals.pig + this._extraAnimals.cattle != 0) {
        dojo.place(
          '<h3>' + _('Excess animals in reserve:') + ' ' + this.computeExtraAnimalsDesc() + '</h3>',
          'exchanges-container',
          'before',
        );
      }

      this.updateExchangeCounters();
    },

    /**
     * Open exchange modal
     */
    openExchangeModal() {
      this._exchangeDialog.show();
    },

    /**
     * Given original reserve and list of exchanges, compute the new reserve
     */
    computeReserveAfterExchange() {
      // Deep copy of originalReserve
      let newReserve = {};
      RESOURCES.forEach((res) => (newReserve[res] = this._originalReserve[res]));

      // Apply exchanges
      this._currentExchanges.forEach((ex) => {
        if (ex.source != 'discard') {
          this.addResourcesArrays(newReserve, this._possibleExchanges[ex].from, -1);
          this.addResourcesArrays(newReserve, this._possibleExchanges[ex].to, 1);
        }
        else {
          this.addResourcesArrays(newReserve, ex.from, -1);
        }
      });

      return newReserve;
    },

    /**
     * Compute extra animals
     */
    computeExtraAnimalsDesc() {
      let newReserve = this.computeReserveAfterExchange();
      let extraAnimals = [];
      ANIMALS.forEach((type) => {
        let n = this._extraAnimals[type] - (this._originalReserve[type] - newReserve[type]);
        if (n > 0) {
          extraAnimals.push(this.formatStringMeeples(n + '<' + type.toUpperCase() + '>'));
        }
      });

      return extraAnimals.join(',');
    },

    /**
     * Compute discarded goods
     */
    computeDiscardedGoodsDesc() {
      discarded = {};
      desc = [];

      this._currentExchanges.forEach((ex) => {
        if (ex.source == 'discard') {
          type = Object.keys(ex.from)[0];
          if (type in discarded) {
            discarded[type]++;
          } else {
            discarded[type] = 1;
          }
        }
      });

      for (var type in discarded) {
        var n = discarded[type];
        if (n > 1) {
          desc.push(this.formatStringMeeples(n + '<' + type.toUpperCase() + '>'));
        } else {
          desc.push(this.formatStringMeeples('<' + type.toUpperCase() + '>'));
        }
      }

      return desc.join(',');
    },

    /**
     * Check if a given exchange (given by index) can be used given new reserve
     */
    canUseExchange(ex) {
      let newReserve = this.computeReserveAfterExchange();
      let exchange = this._possibleExchanges[ex];

      // C62_CookeryExtension: if any exchange with the same flag has already been selected,
      // then all other exchanges in that group are disabled.
      if (exchange.flag && this.isExchangeFlagUsed(exchange.flag)) {
        return false;
      }

      return (
        // Enough resources
        RESOURCES.reduce(
          (acc, res) => acc && newReserve[res] >= (exchange.from[res] == undefined ? 0 : exchange.from[res]),
          true,
        ) &&
        // If max is supplied, shouldn't be reached already
        (exchange.max == undefined || exchange.max > this._currentExchanges.filter((e) => e == ex).length)
      );
    },

    // check if a resource can be discarded
    canDiscard(resource) {
      let newReserve = this.computeReserveAfterExchange();
      if (newReserve[resource] == 0) { return false; }

      return true;
    },

    /**
     * Called when clicking on an exchange
     */
    onClickExchange(ex) {
      let exchange = this._possibleExchanges[ex];
      if (!this.canUseExchange(ex)) return;

      this._currentExchanges.push(ex);
      this.updateExchangeCounters();
    },

    /**
     * Called when clicking on a discard button
     */
    onClickDiscard(resource) {
      let from = {};
      from[resource] = 1;
      let ex = { from, to: {}, max: 9999, source: 'discard'};
      if (!this.canDiscard(resource)) return;

      this._currentExchanges.push(ex);
      this.updateExchangeCounters();
    },

    /**
     * Reset all current exchanges
     */
    clearExchanges() {
      this._currentExchanges = [];
      this.updateExchangeCounters();
    },

    /**
     * Confirm the exchanges
     */
    confirmExchanges() {
      this.takeAtomicAction('actExchange', [this._currentExchanges]);
    },

    /**
     * Exchange notification
     */
    notif_exchange(n) {
      debug('Notif: exchanging resources');

      let mult = this.getAnimationSpeedMultiplier();

      // Close modal if needed
      let initDelay = 0;
      if (this._exchangeDialog) {
        this._exchangeDialog.hide();
        initDelay = Math.round(500 * mult);
      }

      // Flag deleted meeple
      let deleted = n.args.resources;
      deleted.forEach((meeple, i) => {
        meeple.animDelay = Math.round(initDelay + i * 100 * mult);
        meeple.animDestroy = true;
      });
      // Flag created meeple and add DELAY
      let delay = Math.round(initDelay + (800 + deleted.length * 100 + 500) * mult);
      let created = n.args.resources2;
      created.forEach((meeple, i) => {
        meeple.animDelay = Math.round(delay + i * 100 * mult);
        meeple.animDestroy = false;
      });

      // Run animation
      this.slideResources(deleted.concat(created), (meeple) => ({
        delay: meeple.animDelay,
        from: meeple.animDestroy ? null : 'page-title',
        target: meeple.animDestroy ? 'page-title' : null,
        destroy: meeple.animDestroy,
      }));
    },

    /**
     * Update exchange counters and buttons
     */
    computeScoreFromExchanges() {
      let total = 0;
      this._currentExchanges.forEach((ex) => {
        if (typeof ex === 'number') {
          total += this._possibleExchanges[ex].to.score || 0;
        }
      });
      return total;
    },

    updateExchangeCounters() {
      // Update counters
      let newReserve = this.computeReserveAfterExchange();
      RESOURCES.forEach((res) => {
        this._exchangesCounters[res].toValue(newReserve[res]);
      });

      // Update VP indicator
      let score = this.computeScoreFromExchanges();
      let scoreEl = $('exchanges-score-indicator');
      if (scoreEl) {
        scoreEl.innerHTML = score > 0 ? this.formatStringMeeples('+' + score + '<SCORE>') : '';
      }

      // Update disabled state of exchange buttons
      this._possibleExchanges.forEach((exchange, i) => {
        $('exchange-' + i).disabled = !this.canUseExchange(i);
      });

      // Update disabled state of discard buttons
      if (this._exchangeTrigger != BREAD && this._exchangeTrigger != 'huntingTrophy') {
        DISCARDABLE.forEach((resource) => {
          $('discard-' + resource).disabled = true;
        });
      }

      // Update clear/confirm buttons
      dojo.destroy('btnClearExchange');
      if (this._currentExchanges.length > 0) {
        this.addSecondaryActionButton(
          'btnClearExchange',
          _('Clear'),
          () => this.clearExchanges(),
          'exchanges-dialog-footer',
        );
      }

      dojo.destroy('btnConfirmExchange');
      dojo.destroy('btnPassExchange');

      let begging = 0; //this._harvestMinFood - newReserve['food'];
      let extraAnimals = this.computeExtraAnimalsDesc();
      let discardedGoods = this.computeDiscardedGoodsDesc();

      if (begging > 0) {
        this.addDangerActionButton(
          'btnConfirmExchange',
          dojo.string.substitute(this.formatStringMeeples(_('Confirm and take ${begging} <BEGGING>')), { begging }),
          () => this.confirmExchanges(),
          'exchanges-dialog-footer',
        );
      } else if (discardedGoods != '') {
        this.addDangerActionButton(
          'btnConfirmExchange',
          dojo.string.substitute(this.formatStringMeeples(_('Confirm and discard ${discardedGoods}')), { discardedGoods }),
          () => this.confirmExchanges(),
          'exchanges-dialog-footer',
        );
      } else if (extraAnimals != '' && this._mandatoryExchange) {
        this.addDangerActionButton(
          'btnConfirmExchange',
          dojo.string.substitute(this.formatStringMeeples(_('Confirm and discard ${extraAnimals}')), { extraAnimals }),
          () => this.confirmExchanges(),
          'exchanges-dialog-footer',
        );
      } else if (this._currentExchanges.length > 0) {
        this.addPrimaryActionButton(
          'btnConfirmExchange',
          _('Confirm'),
          () => this.confirmExchanges(),
          'exchanges-dialog-footer',
        );
      } else {
        if (
          (this._exchangeTrigger != BREAD && this._exchangeTrigger != 'huntingTrophy' && !this._mandatoryExchange) ||
          this.gamedatas.gamestate.args.optionalAction
        ) {
          this.addSecondaryActionButton(
            'btnPassExchange',
            _('Pass'),
            () => this.takeAction('actPassOptionalAction'),
            'exchanges-dialog-footer',
          );
        }
      }
    },

    /**
     * Add up two arrays of resources
     */
    addResourcesArrays(t1, t2, n = 1) {
      RESOURCES.forEach((res) => (t1[res] += (t2[res] ? t2[res] : 0) * n));
    },

    /**
     * Exchange row template : basically a button
     */
    tplExchangeRow(exchange) {
      let source = exchange.source ? _(exchange.source) : '';
      let arrowN = exchange.max != 9999 ? '-' + exchange.max + 'X' : '';
      let desc = this.formatStringMeeples(
        this.formatResourceArray(exchange.from, false) +
          `<ARROW${arrowN}>` +
          this.formatResourceArray(exchange.to, false),
      );

      return `
          <div class='exchange-source'>${source}</div>
          <button class='exchange-desc' id="exchange-${exchange.id}">${desc}</button>
      `;
    },

    /**
    * C62_Cookery Extension helper. Returns true if an exchange 
    * with the given flag is already selected in the current exchange stack.
    */
    isExchangeFlagUsed(flag) {
      if (!flag) return false;

      return this._currentExchanges.some((ex) => {
        // Discard entries are objects, regular exchanges are indices
        if (typeof ex !== 'number') return false;

        const e = this._possibleExchanges[ex];
        return e && e.flag === flag;
      });
    },

  });
});
