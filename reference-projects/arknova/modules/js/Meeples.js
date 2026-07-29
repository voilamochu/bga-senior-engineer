define(['dojo', 'dojo/_base/declare'], (dojo, declare) => {
  function isVisible(elem) {
    return !!(elem.offsetWidth || elem.offsetHeight || elem.getClientRects().length);
  }

  return declare('arknova.meeples', null, {
    setupMeeples() {
      // This function is refreshUI compatible
      let meepleIds = this.gamedatas.meeples.map((meeple) => {
        if (!$(`meeple-${meeple.id}`)) {
          this.addMeeple(meeple);
        }

        let o = $(`meeple-${meeple.id}`);
        if (!o) return null;

        let container = this.getMeepleContainer(meeple);
        if (o.parentNode != $(container)) {
          dojo.place(o, container);
        }
        o.dataset.state = meeple.state;

        return meeple.id;
      });
      document.querySelectorAll('.arknova-meeple[id^="meeple-"]').forEach((oMeeple) => {
        if (!meepleIds.includes(parseInt(oMeeple.getAttribute('data-id'))) && oMeeple.getAttribute('data-type') != 'cylinder') {
          this.destroy(oMeeple);
        }
      });
      this.updatePlayersCounters();
    },

    addMeeple(meeple, location = null) {
      if ($('meeple-' + meeple.id)) return;

      let o = this.place('tplMeeple', meeple, location == null ? this.getMeepleContainer(meeple) : location);
      let tooltipDesc = this.getMeepleTooltip(meeple);
      if (tooltipDesc != null) {
        this.addCustomTooltip(o.id, tooltipDesc.map((t) => this.formatString(t)).join('<br/>'));
      }

      return o;
    },

    getMeepleTooltip(meeple) {
      let type = meeple.type;
      if (type == 'Venom') {
        return [
          _('Venom token.'),
          _('After using an Action card with Venom token, discard the token.'),
          _(
            'If you did not discard a Venom token during your turn, and there is still a Venom token on at least one of your Action cards, pay <MONEY:2>.'
          ),
          _('In the next break remove all Venom tokens.'),
        ];
      }
      if (type == 'Constriction') {
        return [
          _('Constriction token.'),
          _('Strength of an Action card with Constriction token is decreased by 2.'),
          _('After using an Action card with a Constriction token, discard the token.'),
          _('In the next break remove all Constriction tokens.'),
        ];
      }
      if (type == 'Multiplier') {
        return [
          _('Multiplier token.'),
          _(
            'The next time you execute the action, you may execute it twice. That is, you execute it twice in a row, each time with the same strength X, before placing the Action card in slot 1.'
          ),
          _(
            'You may use X-tokens to strengthen the actions, but each X-token counts for only 1 of the two actions, not for both. You may choose for each of the two actions what you use the action for.'
          ),
          _('Return the Multiplier token to the supply after use.'),
          _('If you have not used the token before the next break, you must return it unused.'),
        ];
      }
      return null;
    },

    tplMeeple(meeple) {
      // BONUS TILE
      if (meeple.type.split('-')[0] == 'bonus') {
        let data = {};
        data[meeple.type] = meeple.state;

        return `<div class="arknova-meeple" id="meeple-${meeple.id}" data-id="${meeple.id}">
          ${this.formatBonus(data)}
        </div>`;
      }

      let type = meeple.type.charAt(0).toLowerCase() + meeple.type.substr(1);
      const PERSONAL = ['token', 'cylinder', 'worker'];
      let color = PERSONAL.includes(type) ? ` data-color="${this.getPlayerColor(meeple.pId)}" ` : '';
      return `<div class="arknova-meeple arknova-icon icon-${type}" id="meeple-${meeple.id}" data-id="${meeple.id}" data-type="${type}" data-state="${meeple.state}" ${color}></div>`;
    },

    getPlayerColor(pId) {
      if (this.gamedatas.players[pId]) {
        return this.gamedatas.players[pId].color;
      } else {
        let colors = ['1863a5', 'b91b1b', 'd1c81c', '000000', '30a638', '7f4e30', '5a5856', 'ffffff', 'c028d3', 'cb7b19'];
        let usedColors = this.getPlayers().map((p) => p.color);
        return colors.find((color) => !usedColors.includes(color));
      }
    },

    getMeepleContainer(meeple) {
      let t = meeple.location.split('_');
      // Workers in reserve
      if (meeple.location == 'reserve') {
        return $(`reserve-${meeple.pId}`);
      }
      // Cubes on bonus spaces of zoo map
      else if (t[0] == 'bonus') {
        return $(`bonus-${meeple.pId}-${t[1]}`);
      }
      // Cubes on continent area (zoo map 9)
      else if (t[0] == 'continent-area') {
        return $(`zoo-map-${meeple.pId}`).querySelector(
          `.continent-area[data-continent='${t[1].toLowerCase()}'] .continent-area-slot`
        );
      }
      // Cylinders on rep track
      else if (t[0] == 'reputation') {
        return $(`reputation-${t[1]}-holder`);
      }
      // Cylinders on conservation track
      else if (t[0] == 'conservation') {
        return $(`conservation-${t[1]}`);
      }
      // Cylinders on duplicated conservation track
      else if (t[0] == 'conservation-duplicate') {
        return t[1] <= 10 ? $(`conservation-duplicate-${t[1]}`) : $('conservation-duplicate-off');
      }
      // Cylinders on appeal track
      else if (t[0] == 'appeal') {
        return $(`appeal-${t[1]}`);
      }
      // Tokens on solo tile
      else if (t[0] == 'solo') {
        return $(`solo-tile-${t[1]}-${t[2]}`);
      }
      // Association board
      else if (t[0] == 'association') {
        let o = $(meeple.location + '_' + meeple.type);
        if (o) return o;
        if (t[1] == 'side') return $('studbooks'); // MW universities
        return $(meeple.location);
      }
      //// Player board ////
      // Workers in supply
      else if (t[0] == 'supply') {
        return $(`worker-${meeple.pId}-${t[1]}`);
      }
      // Partner zoos
      else if (t[0] == 'partner') {
        return $(`partner-${meeple.pId}-${t[1]}`);
      }
      // Universities
      else if (t[0] == 'university') {
        return $(`university-${meeple.pId}-${t[1]}`);
      }
      //// Meeple on cards ////
      else if (t[0] == 'actionCard') {
        return $(`action-card-${t[1]}`).querySelector('.meeples-container');
      } else if ($(`card-${meeple.location}`)) {
        let o = $(`card-${meeple.location}`);
        return o.classList.contains('sponsor-card') ? o.querySelector('.ark-card-wrapper') : o;
      } else if ($(meeple.location)) {
        return $(meeple.location);
      }
      // GRAY BONUS TILE
      else if (t[0] == 'notepad') {
        // MAX HAND +1
        if (meeple.type == 'bonus-increased-hand') return $(`maxHandCount-${meeple.pId}`).parentNode;

        return $(`kept-bonus-container-${meeple.pId}`);
      }

      console.error('Trying to get container of a meeple', meeple);
      return 'game_play_area';
    },

    setupAssociationBoard() {
      [0, 2, 3, 4, 5].forEach((slot) => {
        let content = '';
        // Slot for workers
        if (slot != 0) {
          content += `<div id='association_${slot}' class='worker-slot'></div>`;
        }
        // Donations
        else {
          content += `<div id='association-donation'>`;
          for (let i = -1; i < 8; i++) {
            content += `<div id='association_${slot}_${i}'></div>`;
          }
          content += '</div>';
        }
        // Zoos
        if (slot == 3) {
          ['Africa', 'Europe', 'Asia', 'Americas', 'Australia'].forEach((continent) => {
            content += `<div id='association_${slot}_partner-${continent}' class='continent-slot'></div>`;
          });
        }
        // Universities
        if (slot == 4) {
          ['fac-rep-hand', 'fac-science-rep', 'fac-science-science', 'fac-generic'].forEach((fac) => {
            content += `<div id='association_${slot}_${fac}' class='fac-slot'></div>`;
          });
        }

        $('association-board').insertAdjacentHTML('beforeend', `<div id="association-board-${slot}">${content}</div>`);
      });

      $('association-board').insertAdjacentHTML('beforeend', `<div id="veteranirian-other">*</div>`);
      $('association-board').insertAdjacentHTML(
        'beforeend',
        `<div id="veteranirian-myself">${this.formatString('<STRENGTH:4*>')}</div>`
      );
      this.addCustomTooltip(
        'veteranirian-other',
        _('Someone has played the veteraniarian, allowing them to support conservation project at strength 4')
      );
    },

    /**
     * Wrap the sliding animations
     */
    async slideResources(meeples, configFn, syncNotif = true) {
      let fakeId = -1; // Used for virtual meeple that will get destroyed after animation (eg SCORE)
      let needUpdateForActionCards = false;
      let workerMoved = false;
      let promises = meeples.map((resource, i) => {
        if (resource.type == 'worker') workerMoved = true;

        // Get config for this slide
        let config = typeof configFn === 'function' ? configFn(resource, i) : Object.assign({}, configFn);
        if (resource.destroy) {
          resource.id = fakeId--;
          config.destroy = true;
        }

        // Need update for action cards summaries ?
        if (['Multiplier', 'Constriction', 'Venom'].includes(resource.type)) {
          needUpdateForActionCards = true;
        }

        // Default delay if not specified
        let delay = config.delay ? config.delay : 100 * i;
        config.delay = 0;
        // Use meepleContainer if target not specified
        let target = config.target ? config.target : this.getMeepleContainer(resource);
        if (!isVisible(target)) {
          config.to = $(`overall_player_board_${resource.pId}`);
        }

        // Slide it
        let slideIt = () => {
          // Create meeple if needed
          if (!$('meeple-' + resource.id)) {
            this.addMeeple(resource);
          }

          // Slide it
          return this.slide('meeple-' + resource.id, target, config);
        };

        if (this.isFastMode()) {
          slideIt();
          return null;
        } else {
          return this.wait(delay - 10).then(slideIt);
        }
      });

      if (!this.isFastMode()) {
        await Promise.all(promises);
        await this.wait(10);
      }

      if (workerMoved) {
        this.updateWorkerCounters();
      }
      if (needUpdateForActionCards) {
        this.updateActionCardsSummaries();
      }
    },

    async notif_addMeeples(n) {
      debug('Notif: adding & sliding meeples', n);
      await this.slideResources(n.args.meeples, {
        from: this.getVisibleTitleContainer(),
      });
    },

    async notif_donation(n) {
      debug('Notif: making a donation', n);

      if (n.args.meeple) {
        await this.slideResources(
          [n.args.meeple],
          {
            from: this.getVisibleTitleContainer(),
          },
          false
        );
      }

      n.args.bonuses.conservation = 1;
      await this.notif_getBonuses(n);
      this._scoreCounters[n.args.player_id].toValue(n.args.score);
    },

    async notif_slideMeeples(n) {
      debug('Notif: sliding meeples', n);
      await this.slideResources(n.args.meeples);
      this.updateCardCosts();
    },

    notif_enableMultiplier(n) {
      debug('Notif: enabling multiplier', n);
      n.args.meepleIds.forEach((mId) => ($(`meeple-${mId}`).dataset.state = 1));
      this.updateActionCardsSummaries();
    },

    async notif_discardTokens(n) {
      debug('Notif: discard a token', n);
      let meeples = n.args.meeples.filter((meeple) => $(`meeple-${meeple.id}`));

      await this.slideResources(meeples, {
        destroy: true,
        to: this.getVisibleTitleContainer(),
        phantom: false,
      });
    },

    async notif_markCard(n) {
      debug('Notif: marking cards', n);
      await this.slideResources(n.args.meeples, {
        from: this.getVisibleTitleContainer(),
      });
    },
  });
});
