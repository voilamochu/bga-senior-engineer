define(['dojo', 'dojo/_base/declare'], (dojo, declare) => {
  return declare('agricola.specialEffect', null, {
    _specialStorage: {},
    /**
     * Entering the exchange state : create modal and init selection
     */
    onEnteringStateSpecialEffect(args) {
      if (args.description) {
        this.gamedatas.gamestate.descriptionmyturn = this.formatStringMeeples(args.descriptionmyturn);
        this.gamedatas.gamestate.description = this.formatStringMeeples(args.description);
        this.gamedatas.gamestate.args.nb = args.nb;
        this.updatePageTitle();
      }
      if (!this.isCurrentPlayerActive()) {
        dojo.destroy('btnPassAction');
        dojo.destroy('btnRestartTurn');
        return;
      }

      let method = args.cardId;
      if (this[method] != undefined) {
        this[method](args);
      }
    },

    A3_PaperKnife(args) {
      this.promptCardMultiple(['occupation'], args.cards, 3, (cards) => this.takeAtomicAction('actA3', [cards]));
    },

    B83_MuddyPuddles(args) {
      this._specialStorage = { n: 1 };
      this.B83_refresh(args);
    },

    B83_refresh(args) {
      dojo.destroy('btnB83Minus');
      dojo.destroy('btnB83Plus');
      dojo.destroy('btnB83Confirm');

      const n = this._specialStorage.n;

      this.addSecondaryActionButton('btnB83Minus', '-', () => {
        if (this._specialStorage.n > 1) {
          this._specialStorage.n--;
          this.B83_refresh(args);
        }
      });
      if (n <= 1) dojo.addClass('btnB83Minus', 'disabled');

      this.addSecondaryActionButton('btnB83Plus', '+', () => {
        if (this._specialStorage.n < args.max) {
          this._specialStorage.n++;
          this.B83_refresh(args);
        }
      });
      if (n >= args.max) dojo.addClass('btnB83Plus', 'disabled');

      let preview = args.goods.slice(0, n).map((g) => '<' + g.type.toUpperCase() + '>').join(', ');
      let maxPreview = args.goods.slice(0, args.max).map((g) => '<' + g.type.toUpperCase() + '>').join(', ');
      this.addPrimaryActionButton('btnB83Confirm',
        this.formatStringMeeples('Pay ' + args.max + '<CLAY>, buy ' + maxPreview),
        () => {
          this.takeAtomicAction('actB83', [this._specialStorage.n]);
        },
      );
      let btn = $('btnB83Confirm');
      dojo.style(btn, 'minWidth', btn.offsetWidth + 'px');
      btn.innerHTML = this.formatStringMeeples('Pay ' + n + '<CLAY>, buy ' + preview);
    },

    B146_Illusionist(args) {
      this.promptCard(['occupation','minor'], args.cards, (cards) => this.takeAtomicAction('actB146', [cards]));
    },

    A58_AsparagusKnife(args) {
      this.promptPlayerBoardZones(args.zones, 1, 1, (zones) => this.takeAtomicAction('actA58', [zones]));
    },

    A70_LiftingMachine(args) {
      this.promptPlayerBoardZones(args.zones, 1, 1, (zones) => this.takeAtomicAction('actA70', [zones]));
    },

    D71_Changeover(args) {
      this.promptPlayerBoardZones(args.sources, 1, 1, (zones) => this.takeAtomicAction('actD71', [zones]));
    },

    A84_Silage(args) {
      this.promptPlayerBoardZones(args.sources, 1, 1, (zones) => this.takeAtomicAction('actA84', [zones]));
    },

    B85_FarmHand(args) {
      this.promptPlayerBoardZones(args.zones, 1, 1, (zones) => this.takeAtomicAction('actB85', [zones]));
    },

    B165_GameProvider(args) {
      this.promptPlayerBoardZones(args.sources, 1, 4, (zones) => this.takeAtomicAction('actB165', [zones]));
    },

    C18_RollOverPlow(args) {
      this.promptPlayerBoardZones(args.sources, 1, 2, (zones) => this.takeAtomicAction('actC18', [zones]));
    },

    D70_StrawManure(args) {
      this.promptPlayerBoardZones(args.sources, 1, 2, (zones) => this.takeAtomicAction('actD70', [zones]));
    },

    D72_StableManure(args) {
      this.promptPlayerBoardZones(args.zones, 1, 4, (zones) => this.takeAtomicAction('actD72', [zones]));
    },

    A71_ClearingSpade(args) {
      // Separate a set of zone into sources/targets
      let separate = (zones) => {
        let s = [],
          t = [];
        zones.forEach((zone) => {
          if (args.sources.includes(zone)) s.push(zone);
          else t.push(zone);
        });
        return [s, t];
      };

      this.promptPlayerBoardZones(
        args.sources.concat(args.targets),
        1,
        2,
        (zones) => this.takeAtomicAction('actA71', separate(zones)),
        (zones) => {
          let fzones = separate(zones);
          return fzones[0].length == fzones[1].length; // Same number of sources and targets
        },
      );
    },

    A102_Grocer(args) {
      this._specialStorage = { n: 1 };
      this.A102_refresh(args);
    },

    A102_refresh(args) {
      dojo.destroy('btnA102Minus');
      dojo.destroy('btnA102Plus');
      dojo.destroy('btnA102Confirm');

      const n = this._specialStorage.n;

      this.addSecondaryActionButton('btnA102Minus', '-', () => {
        if (this._specialStorage.n > 1) {
          this._specialStorage.n--;
          this.A102_refresh(args);
        }
      });
      if (n <= 1) dojo.addClass('btnA102Minus', 'disabled');

      this.addSecondaryActionButton('btnA102Plus', '+', () => {
        if (this._specialStorage.n < args.max) {
          this._specialStorage.n++;
          this.A102_refresh(args);
        }
      });
      if (n >= args.max) dojo.addClass('btnA102Plus', 'disabled');

      let preview = args.goods.slice(0, n).map((g) => '<' + g.type.toUpperCase() + '>').join(', ');
      let maxPreview = args.goods.slice(0, args.max).map((g) => '<' + g.type.toUpperCase() + '>').join(', ');
      this.addPrimaryActionButton('btnA102Confirm',
        this.formatStringMeeples('Pay ' + args.max + '<FOOD>, buy ' + maxPreview),
        () => {
          this.takeAtomicAction('actA102', [this._specialStorage.n]);
        },
      );
      let btn = $('btnA102Confirm');
      dojo.style(btn, 'minWidth', btn.offsetWidth + 'px');
      btn.innerHTML = this.formatStringMeeples('Pay ' + n + '<FOOD>, buy ' + preview);
    },

    A112_ScytheWorker(args) {
      this.promptPlayerBoardZones(args.zones, 1, 15, (zones) => this.takeAtomicAction('actA112', [zones]));
    },

    C57_Crudite(args) {
      this.promptPlayerBoardZones(args.sources, 1, args.sources.length, (zones) =>
        this.takeAtomicAction('actC57', [zones]),
      );
    },

    C63_CraftBrewery(args) {
      this.promptPlayerBoardZones(args.sources, 1, 1, (zones) => this.takeAtomicAction('actC63', [zones]));
    },

    C69_LandConsolidation(args) {
      this.promptPlayerBoardZones(args.sources, 1, 1, (zones) => this.takeAtomicAction('actC69', [zones]));
    },

    C104_Collector(args) {
      this._specialStorage = { nb: args.nb, selected: [] };
      ['food', 'wood', 'clay', 'stone', 'reed', 'grain', 'vegetable', 'sheep', 'pig', 'cattle'].forEach((resource) => {
        this.addPrimaryActionButton(
          resource + '-button',
          this.formatStringMeeples('<' + resource.toUpperCase() + '>'),
          () => {
            let sel = this._specialStorage.selected;
            let idx = sel.indexOf(resource);
            if (idx !== -1) {
              sel.splice(idx, 1);
            } else if (sel.length < this._specialStorage.nb) {
              sel.push(resource);
            }
            this.C104_refresh();
          },
        );
      });

      this.addPrimaryActionButton('btnConfirmC104', _('Confirm'), () => {
        this.takeAtomicAction('actC104', [this._specialStorage.selected]);
      });
      dojo.addClass('btnConfirmC104', 'disabled');
    },

    C104_refresh() {
      let sel = this._specialStorage.selected;
      ['food', 'wood', 'clay', 'stone', 'reed', 'grain', 'vegetable', 'sheep', 'pig', 'cattle'].forEach((r) => {
        dojo.toggleClass(r + '-button', 'btn-selected', sel.includes(r));
      });
      dojo.toggleClass('btnConfirmC104', 'disabled', sel.length !== this._specialStorage.nb);
    },

    D132_HideFarmer(args) {
      for (let i = 0; i <= args.max; i++) {
        let amount = i;
        this.addPrimaryActionButton(amount + '-button', amount, () => this.takeAtomicAction('actD132', [amount]));
      }
    },

    D137_TradeTeacher(args) {
      this._specialStorage = { selected: [] };
      ['grain', 'stone', 'sheep', 'pig', 'cattle', 'vegetable'].forEach((resource) => {
        this.addPrimaryActionButton(
          resource + '-button',
          this.formatStringMeeples('<' + resource.toUpperCase() + '>'),
          () => {
            let sel = this._specialStorage.selected;
            let idx = sel.indexOf(resource);
            if (idx !== -1) {
              sel.splice(idx, 1);
            } else if (sel.length < 2) {
              sel.push(resource);
            }
            this.D137_refresh();
          },
        );
      });

      this.addPrimaryActionButton('btnConfirmD137',
        this.formatStringMeeples('Confirm, pay 4<FOOD>'),
        () => {
          this.takeAtomicAction('actD137', [this._specialStorage.selected]);
        },
      );
      let btnD137 = $('btnConfirmD137');
      dojo.style(btnD137, 'minWidth', btnD137.offsetWidth + 'px');
      btnD137.innerHTML = _('Confirm');
      dojo.addClass('btnConfirmD137', 'disabled');
    },

    D137_refresh() {
      let sel = this._specialStorage.selected;
      ['grain', 'stone', 'sheep', 'pig', 'cattle', 'vegetable'].forEach((r) => {
        dojo.toggleClass(r + '-button', 'btn-selected', sel.includes(r));
      });

      let cost = 0;
      sel.forEach((r) => {
        cost += ['cattle', 'vegetable'].includes(r) ? 2 : 1;
      });

      let btnConfirm = $('btnConfirmD137');
      if (sel.length > 0) {
        btnConfirm.innerHTML = this.formatStringMeeples('Confirm, pay ' + cost + '<FOOD>');
        dojo.removeClass('btnConfirmD137', 'disabled');
      } else {
        btnConfirm.innerHTML = _('Confirm');
        dojo.addClass('btnConfirmD137', 'disabled');
      }
    },

    E22_GuestRoom(args) {
      this._specialStorage = { n: 1 };
      this.E22_refresh(args);
    },

    E22_refresh(args) {
      dojo.destroy('btnE22Minus');
      dojo.destroy('btnE22Plus');
      dojo.destroy('btnE22Confirm');

      const n = this._specialStorage.n;

      this.addSecondaryActionButton('btnE22Minus', '-', () => {
        if (this._specialStorage.n > 1) {
          this._specialStorage.n--;
          this.E22_refresh(args);
        }
      });
      if (n <= 1) dojo.addClass('btnE22Minus', 'disabled');

      this.addSecondaryActionButton('btnE22Plus', '+', () => {
        if (this._specialStorage.n < args.max) {
          this._specialStorage.n++;
          this.E22_refresh(args);
        }
      });
      if (n >= args.max) dojo.addClass('btnE22Plus', 'disabled');

      this.addPrimaryActionButton('btnE22Confirm',
        this.formatStringMeeples('Pay ' + args.max + '<FOOD>'),
        () => {
          this.takeAtomicAction('actE22', [this._specialStorage.n]);
        },
      );
      let btn = $('btnE22Confirm');
      dojo.style(btn, 'minWidth', btn.offsetWidth + 'px');
      btn.innerHTML = this.formatStringMeeples('Pay ' + n + '<FOOD>');
    },

    A136_DrudgeryReeve(args) {
      for (let i = 0; i <= args.max; i++) {
        let amount = i;
        this.addPrimaryActionButton(amount + '-button', amount, () => this.takeAtomicAction('actA136', [amount]));
      }
    },

    B115_TinsmithMaster(args) {
      this.promptPlayerBoardZones(args.zones, 1, 15, (zones) => this.takeAtomicAction('actB115', [zones]));
    },

    C133_Soldier(args) {
      for (let i = 0; i <= args.max; i++) {
        let amount = i;
        this.addPrimaryActionButton(amount + '-button', amount, () => this.takeAtomicAction('actC133', [amount]));
      }
    },

    D51_Archway(args) {
      this.promptActionCard(args.spaces, (space) => this.takeAtomicAction('actD51', [space, args.farmer]));
    },

    D93_SheepInspector(args) {
      this.promptActionCard(args.spaces, (spaces) => this.takeAtomicAction('actD93', [spaces]));
    },

    D102_SampleStableMaker(args) {
      this.promptPlayerBoardZones(args.zones, 1, 1, (zones) => {
        this.takeAtomicAction('actD102', [zones]);
      });
    },

    E4_Thunderbolt(args) {
      this.promptPlayerBoardZones(args.zones, 1, 1, (zone) => this.takeAtomicAction('actE4', [zone]));
    },

    E10_StrawHat(args) {
      this.promptActionCard(args.spaces, (space) => this.takeAtomicAction('actE10', [space, args.farmer]));
    },

    E71_CowPatty(args) {
      this.promptPlayerBoardZones(args.zones, 1, 15, (zones) => this.takeAtomicAction('actE71', [zones]));
    },

    E73_Scythe(args) {
      this.promptPlayerBoardZones(args.zones, 1, 1, (zone) => this.takeAtomicAction('actE73', [zone]));
    },
    E76_LumberPile(args) {
      const method = args.method;

      if (method === 'chooseFarmHandFirst') {
        // Two explicit buttons: Return Farm Hand stable or Pass into the normal flow
        this.addPrimaryActionButton('btnReturnFarmHand', _('Return Farm Hand stable'), () => {
          this.takeAtomicAction('actE76', ['farmhand']);
        });

        this.addSecondaryActionButton('btnPassFarmHand', _('Pass'), () => {
          this.takeAtomicAction('actE76', ['pass']);
        });

        return;
      }

      if (method === 'returnStables' || method === 'returnMoreStables') {
        const max = args.max ?? 3;
        this.promptPlayerBoardZones(args.zones, 1, max, (zones) => {
          this.takeAtomicAction('actE76', [zones]);
        });
        return;
      }
    },

    B157_Salter(args) {
      this._specialStorage = { selected: { sheep: 0, pig: 0, cattle: 0 }, reserve: args.reserve };

      ['sheep', 'pig', 'cattle'].forEach((type) => {
        this.addPrimaryActionButton(
          type + '-salt-button',
          this.formatStringMeeples('<' + type.toUpperCase() + '>'),
          () => this.B157_add(type),
        );
        if (args.reserve[type] <= 0) {
          dojo.addClass(type + '-salt-button', 'disabled');
        }
      });

      this.addSecondaryActionButton('btnClearB157', _('Clear'), () => this.B157_clear());
      dojo.addClass('btnClearB157', 'disabled');

      let maxLabel = this.formatStringMeeples(
        _('Confirm, salt') + ' 00<SHEEP>, 00<PIG>, 00<CATTLE>'
      );
      this.addPrimaryActionButton('btnConfirmB157', maxLabel, () => this.B157_confirm());
      let btn = $('btnConfirmB157');
      dojo.style(btn, 'minWidth', btn.offsetWidth + 'px');
      btn.innerHTML = _('Confirm');
      dojo.addClass('btnConfirmB157', 'disabled');
    },

    B157_add(type) {
      if (dojo.hasClass(type + '-salt-button', 'disabled')) return;

      let s = this._specialStorage;
      s.selected[type]++;

      if (s.selected[type] >= s.reserve[type]) {
        dojo.addClass(type + '-salt-button', 'disabled');
      }

      this.B157_refresh();
    },

    B157_clear() {
      if (dojo.hasClass('btnClearB157', 'disabled')) return;

      let s = this._specialStorage;
      s.selected = { sheep: 0, pig: 0, cattle: 0 };
      ['sheep', 'pig', 'cattle'].forEach((t) => {
        if (s.reserve[t] > 0) {
          dojo.removeClass(t + '-salt-button', 'disabled');
        }
      });
      this.B157_refresh();
    },

    B157_refresh() {
      let s = this._specialStorage;
      let total = s.selected.sheep + s.selected.pig + s.selected.cattle;
      let btn = $('btnConfirmB157');

      if (total === 0) {
        btn.innerHTML = _('Confirm');
        dojo.addClass('btnConfirmB157', 'disabled');
        dojo.addClass('btnClearB157', 'disabled');
        return;
      }

      let parts = [];
      ['sheep', 'pig', 'cattle'].forEach((t) => {
        if (s.selected[t] > 0) {
          parts.push(s.selected[t] + '<' + t.toUpperCase() + '>');
        }
      });

      btn.innerHTML = this.formatStringMeeples(_('Confirm, salt') + ' ' + parts.join(', '));
      dojo.removeClass('btnConfirmB157', 'disabled');
      dojo.removeClass('btnClearB157', 'disabled');
    },

    B157_confirm() {
      if (dojo.hasClass('btnConfirmB157', 'disabled')) return;
      let s = this._specialStorage.selected;
      this.takeAtomicAction('actB157', [s.sheep, s.pig, s.cattle]);
    },

    E106_EmergencySeller(args) {
      this._specialStorage = { discard: [], reserve: args.reserve, max: args.nb };

      ['wood', 'clay', 'reed', 'stone'].forEach((res) => {
        this.addPrimaryActionButton(
          res + '-sell-button',
          this.formatStringMeeples('<' + res.toUpperCase() + '>'),
          () => this.E106_sell(res),
        );
        if (args.reserve[res] <= 0) {
          dojo.addClass(res + '-sell-button', 'disabled');
        }
      });

      this.addSecondaryActionButton('btnClearE106', _('Clear'), () => this.E106_clear());
      dojo.addClass('btnClearE106', 'disabled');

      // Confirm — pre-size using a worst-case label so it never jumps
      let maxLabel = this.formatStringMeeples(
        _('Confirm, sell') + ' 00<WOOD>, 00<CLAY>, 00<REED>, 00<STONE>' + _(', gain') + ' 00<FOOD>'
      );
      this.addPrimaryActionButton('btnConfirmE106', maxLabel, () => this.E106_confirm());
      let btn = $('btnConfirmE106');
      dojo.style(btn, 'minWidth', btn.offsetWidth + 'px');
      btn.innerHTML = _('Confirm');
      dojo.addClass('btnConfirmE106', 'disabled');
    },

    E106_sell(res) {
      if (dojo.hasClass(res + '-sell-button', 'disabled')) return;

      let s = this._specialStorage;
      s.discard.push(res);

      // Disable this resource's button if its reserve is used up
      let sold = s.discard.filter((x) => x == res).length;
      if (sold >= s.reserve[res]) {
        dojo.addClass(res + '-sell-button', 'disabled');
      }

      // Disable all sell buttons if reached max
      if (s.discard.length >= s.max) {
        ['wood', 'clay', 'reed', 'stone'].forEach((r) => dojo.addClass(r + '-sell-button', 'disabled'));
      }

      this.E106_refresh();
    },

    E106_clear() {
      if (dojo.hasClass('btnClearE106', 'disabled')) return;

      let s = this._specialStorage;
      s.discard = [];
      ['wood', 'clay', 'reed', 'stone'].forEach((r) => {
        if (s.reserve[r] > 0) {
          dojo.removeClass(r + '-sell-button', 'disabled');
        }
      });
      this.E106_refresh();
    },

    E106_refresh() {
      let s = this._specialStorage;
      let btn = $('btnConfirmE106');

      if (s.discard.length === 0) {
        btn.innerHTML = _('Confirm');
        dojo.addClass('btnConfirmE106', 'disabled');
        dojo.addClass('btnClearE106', 'disabled');
        return;
      }

      let counts = {};
      let food = 0;
      s.discard.forEach((r) => {
        counts[r] = (counts[r] || 0) + 1;
        food += (r === 'reed' || r === 'stone') ? 3 : 2;
      });

      let parts = [];
      ['wood', 'clay', 'reed', 'stone'].forEach((r) => {
        if (counts[r]) {
          parts.push(counts[r] + '<' + r.toUpperCase() + '>');
        }
      });

      btn.innerHTML = this.formatStringMeeples(
        _('Confirm, sell') + ' ' + parts.join(', ') + _(', gain') + ' ' + food + '<FOOD>'
      );
      dojo.removeClass('btnConfirmE106', 'disabled');
      dojo.removeClass('btnClearE106', 'disabled');
    },

    E106_confirm() {
      if (dojo.hasClass('btnConfirmE106', 'disabled')) return;
      this.takeAtomicAction('actE106', [this._specialStorage.discard]);
    },

    E78_SleightofHand(args) {
      this._specialStorage = { discard: [], receive: [] };
      ['wood', 'clay', 'reed', 'stone'].forEach((resource) => {
        if (args.reserve[resource] > 0) {
          this.addDangerActionButton(
            resource + '-discard-button',
            this.formatStringMeeples('<' + resource.toUpperCase() + '>'),
            () => this.E78_discard(resource, args.reserve),
          );
        }
      });

      ['wood', 'clay', 'reed', 'stone'].forEach((resource) => {
        this.addPrimaryActionButton(
          resource + '-button',
          this.formatStringMeeples('<' + resource.toUpperCase() + '>'),
          () => this.E78_select(resource),
        );

        dojo.addClass(resource + '-button', 'disabled');
      });
    },

    E78_discard(resource, reserve) {
      if (dojo.hasClass(resource + '-discard-button', 'disabled')) {
        return;
      }

      this._specialStorage.discard.push(resource);

      if (this._specialStorage.discard.length >= 4) {
        if (dojo.exists('wood-discard-button')) {dojo.addClass('wood-discard-button', 'disabled');}
        if (dojo.exists('clay-discard-button')) {dojo.addClass('clay-discard-button', 'disabled');}
        if (dojo.exists('reed-discard-button')) {dojo.addClass('reed-discard-button', 'disabled');}
        if (dojo.exists('stone-discard-button')) {dojo.addClass('stone-discard-button', 'disabled');}
      }

      if (this._specialStorage.discard.filter(x => x == resource).length >= reserve[resource]) {
        dojo.addClass(resource + '-discard-button', 'disabled');
      }

      if (this._specialStorage.discard.length > this._specialStorage.receive.length) {
        dojo.removeClass('wood-button', 'disabled');
        dojo.removeClass('clay-button', 'disabled');
        dojo.removeClass('reed-button', 'disabled');
        dojo.removeClass('stone-button', 'disabled');
      } else {
        dojo.addClass('wood-button', 'disabled');
        dojo.addClass('clay-button', 'disabled');
        dojo.addClass('reed-button', 'disabled');
        dojo.addClass('stone-button', 'disabled');
      }

      this.E78AddConfirm();

      this.addSecondaryActionButton('btnClearE78', _('Clear'), () => {
        this._specialStorage.discard = [];
        this._specialStorage.receive = [];
        dojo.query('#customActions .action-button').removeClass('disabled');
        dojo.destroy('btnClearE78');
        dojo.destroy('btnConfirmE78');
        dojo.addClass('wood-button', 'disabled');
        dojo.addClass('clay-button', 'disabled');
        dojo.addClass('reed-button', 'disabled');
        dojo.addClass('stone-button', 'disabled');
      });
    },

    E78_select(resource) {
      if (dojo.hasClass(resource + '-button', 'disabled')) {
        return;
      }

      this._specialStorage.receive.push(resource);

      if (this._specialStorage.receive.length >= 4 || (this._specialStorage.discard.length <= this._specialStorage.receive.length)) {
        dojo.addClass('wood-button', 'disabled');
        dojo.addClass('clay-button', 'disabled');
        dojo.addClass('reed-button', 'disabled');
        dojo.addClass('stone-button', 'disabled');
      }

      this.E78AddConfirm();
    },

    E78Format(array) {
      pay = {};
      desc = [];

      array.forEach((res) => {
        if (res in pay) {
          pay[res]++;
        } else {
          pay[res] = 1;
        }
      });

      for (var res in pay) {
        var n = pay[res];
        if (n > 1) {
          desc.push(this.formatStringMeeples(n + '<' + res.toUpperCase() + '>'));
        } else {
          desc.push(this.formatStringMeeples('<' + res.toUpperCase() + '>'));
        }
      }

      return desc.join(',');
    },

    E78AddConfirm() {
      dojo.destroy('btnConfirmE78');
      let pay = this.E78Format(this._specialStorage.discard);

      let string = 'Confirm, pay ' + pay;
      if (this._specialStorage.receive.length > 0) {
        let receive = this.E78Format(this._specialStorage.receive);
        string = string + ', gain ' + receive;
      }

      this.addPrimaryActionButton(
        'btnConfirmE78',
        this.formatStringMeeples(string),
        () => this.E78Confirm(),
      );

      if (this._specialStorage.discard.length != this._specialStorage.receive.length) {
        dojo.addClass('btnConfirmE78', 'disabled');
      }
    },

    E78Confirm() {
      if (dojo.hasClass('btnConfirmE78', 'disabled')) {
        return;
      }

      this.takeAtomicAction('actE78', [this._specialStorage.discard, this._specialStorage.receive])
    },

    E148_Lazybones(args) {
      this.promptActionCardMultiple(args.spaces, 0, args.max, (spaces) => this.takeAtomicAction('actE148', [spaces]));
    },

    E74_AshTrees(args) {
      for (let i = 1; i <= args.max; i++) {
        let amount = i;
        this.addPrimaryActionButton(amount + '-button', amount, () => this.takeAtomicAction('actE74', [amount]));
      }
    },

    E112_GrainThief(args) {
      this.promptPlayerBoardZones(args.zones, 1, 15, (zones) => this.takeAtomicAction('actE112', [zones]));
    },

    E85_MasterTanner(args) {
      for (let i = 1; i <= args.max; i++) {
        let amount = i;
        this.addPrimaryActionButton(amount + '-button', amount, () => this.takeAtomicAction('actE85', [amount]));
      }
    },

    E149_MidnightFencer(args, zones) {
      // Store fencing selection + donor constraints
      this._specialStorage = {
        fences: zones,
        caps: args.donorCaps || {},
        donors: {},
      };

      Object.keys(this._specialStorage.caps).forEach((pid) => {
        this._specialStorage.donors[pid] = 0;
      });

      this.E149_refreshDonorButtons();
    },

    E149_refreshDonorButtons() {
      // Clear previous E149 UI
      dojo.query('#customActions .action-button').forEach((n) => dojo.destroy(n));

      const s = this._specialStorage;
      const total = s.fences.length;
      const used = Object.values(s.donors).reduce((a, b) => a + b, 0);
      const remaining = total - used;

      // One "Take 1 fence" button per opponent
      Object.keys(s.caps).forEach((pid) => {
        const cap = s.caps[pid];
        if (!cap || cap <= 0) return;

        const current = s.donors[pid] || 0;
        const name =
          this.gamedatas && this.gamedatas.players && this.gamedatas.players[pid]
            ? this.gamedatas.players[pid].name
            : pid;

        this.addPrimaryActionButton(
          `E149_take_${pid}`,
          `${_('Take 1 fence from')} ${name} (${current}/${cap})`,
          () => {
            if (dojo.hasClass(`E149_take_${pid}`, 'disabled')) return;

            // Don’t allow taking more than needed overall
            const usedNow = Object.values(s.donors).reduce((a, b) => a + b, 0);
            if (usedNow >= total) return;

            // Don’t allow taking more than this donor’s cap
            if ((s.donors[pid] || 0) >= cap) return;

            s.donors[pid] = (s.donors[pid] || 0) + 1;
            this.E149_refreshDonorButtons();
          }
        );

        // Disable if no remaining overall or at cap
        if (remaining <= 0 || current >= cap) {
          dojo.addClass(`E149_take_${pid}`, 'disabled');
        }
      });

      // Clear
      this.addSecondaryActionButton('E149_clear', _('Clear'), () => {
        Object.keys(s.donors).forEach((pid) => (s.donors[pid] = 0));
        this.E149_refreshDonorButtons();
      });

      // Confirm (enabled only when fully allocated)
      this.addPrimaryActionButton('E149_confirm', _('Confirm'), () => this.E149_confirmDonors());
      if (remaining !== 0) {
        dojo.addClass('E149_confirm', 'disabled');
      }
    },

    E149_confirmDonors() {
      if (dojo.hasClass('E149_confirm', 'disabled')) return;
      this.takeAtomicAction('actFence', [this._specialStorage.fences, this._specialStorage.donors]);
    },

    C146_WorkshopAssistant(args) {
      const method = args.method;

      if (method === 'choosePairs') {
        this._specialStorage = { chosen: [], n: args.n };

        args.pairs.forEach((p) => {
          this.addPrimaryActionButton(
            `C146_pair_${p.key}`,
            this.formatStringMeeples(p.label),
            () => {
              let sel = this._specialStorage.chosen;
              let idx = sel.indexOf(p.key);
              if (idx !== -1) {
                sel.splice(idx, 1);
              } else if (sel.length < this._specialStorage.n) {
                sel.push(p.key);
              }
              this.C146_refresh(args);
            }
          );
        });

        this.addPrimaryActionButton('btnConfirmC146', _('Confirm'), () => {
          this.takeAtomicAction('actC146', [this._specialStorage.chosen]);
        });
        dojo.addClass('btnConfirmC146', 'disabled');

        return;
      }

      if (method === 'takePair') {
        args.pairs.forEach((p) => {
          this.addPrimaryActionButton(
            `C146_take_${p.key}`,
            this.formatStringMeeples(`Take ${p.label}`),
            () => this.takeAtomicAction('actC146', [p.key])
          );
        });

        return;
      }
    },

    C146_refresh(args) {
      let sel = this._specialStorage.chosen;
      args.pairs.forEach((p) => {
        dojo.toggleClass(`C146_pair_${p.key}`, 'btn-selected', sel.includes(p.key));
      });
      dojo.toggleClass('btnConfirmC146', 'disabled', sel.length !== this._specialStorage.n);
    },

    E5_NightLoot(args) {
      this._specialStorage = { nb: args.nb, selected: [], options: args.options };
      args.options.forEach((opt) => {
        let btnId = 'E5_' + opt.spaceId + '_' + opt.type;
        this.addPrimaryActionButton(
          btnId,
          this.formatStringMeeples(
            this.format_string_recursive(_('${resources_desc} from ${action_space}'), {
              resources_desc: '<' + opt.type.toUpperCase() + '>',
              action_space: _(opt.spaceName),
              i18n: ['action_space'],
            }),
          ),
          () => this.E5_toggle(opt),
        );
      });

      if (args.nb > 1) {
        this.addPrimaryActionButton('btnConfirmE5', _('Confirm'), () => {
          this.takeAtomicAction('actE5', [this._specialStorage.selected]);
        });
        dojo.addClass('btnConfirmE5', 'disabled');
      }
    },

    E5_toggle(opt) {
      let s = this._specialStorage;
      let idx = s.selected.findIndex((e) => e.spaceId == opt.spaceId && e.type == opt.type);

      if (idx !== -1) {
        s.selected.splice(idx, 1);
      } else if (s.selected.length < s.nb) {
        s.selected.push({ spaceId: opt.spaceId, type: opt.type });

        // If only need 1, submit immediately
        if (s.nb == 1) {
          this.takeAtomicAction('actE5', [s.selected]);
          return;
        }
      }

      this.E5_refresh();
    },

    E5_refresh() {
      let s = this._specialStorage;
      let selectedTypes = s.selected.map((e) => e.type);

      s.options.forEach((o) => {
        let id = 'E5_' + o.spaceId + '_' + o.type;
        let btn = $(id);
        let isSel = s.selected.some((e) => e.spaceId == o.spaceId && e.type == o.type);
        let typeSelected = selectedTypes.includes(o.type);

        // Hide other sources of an already-selected type
        dojo.style(btn, 'display', (!isSel && typeSelected) ? 'none' : '');
        dojo.toggleClass(btn, 'btn-selected', isSel);
      });

      if (s.nb > 1) {
        dojo.toggleClass('btnConfirmE5', 'disabled', s.selected.length !== s.nb);
      }
    },

  });
});
