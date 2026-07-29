define(['dojo', 'dojo/_base/declare'], (dojo, declare) => {
  const resources = ['wood', 'clay', 'reed', 'stone', 'grain', 'vegetable', 'food', 'sheep', 'pig', 'cattle'];

  return declare('agricola.playerBoard', null, {
    getCell(pos, pId = null) {
      if (pId == null) {
        pId = pos.pId ? pos.pId : this.player_id;
      }

      // Handle fieldCards
      if ((pos.x == -1 && pos.y == -1) || (pos.id && pos.type && pos.type == 'fieldCard' && $(pos.id))) {
        let cardId = pos.id || pos.location;
        let elem = $(cardId).querySelector('.player-card-field-cell');
        dojo.addClass(elem, 'active');
        return elem;
      }

      return document.querySelector(`#board-${pId} .board-cell[data-x='${pos.x}'][data-y='${pos.y}']`);
    },

    /**
     * Player board tpl
     */
    tplPlayerBoard(player) {
      let current = player.id == this.player_id ? 'current' : '';
      if (this.isSpectator && Object.keys(this.gamedatas.players)[0] == player.id) {
        current = 'current';
      }
      let name = player.id == this.player_id ? _('Your board') : player.name;
      let html =
        `
    <div class='player-board-resizable' id='player-board-resizable-${player.id}'>
      <div class="player-board-wrapper ${current}" id="board-wrapper-${player.id}">
        <div class="player-board-holder">
          <div class="animals-counters">
            ` +
        this.tplResourceCounter(player, 'sheep') +
        this.tplResourceCounter(player, 'pig') +
        this.tplResourceCounter(player, 'cattle') +
        `
          </div>
          <div id="resources-bar-holder-${player.id}" class="resources-bar-holder">
            <div class="player-board-name" style="border-color:#${player.color}; color:#${player.color}">
              <span id="farmer_cluster_${player.id}" class="farmer-cluster" data-color="${player.color}"></span><span class="player-board-name-text">${name}</span>
            </div>
          </div>
          <div class="agricola-player-board" id="board-${player.id}" data-color="${player.color}">
            <div class="player-board-grid">
        `;
      for (let y = 0; y <= 6; y++) {
        for (let x = 0; x <= 10; x++) {
          let type = 'edge';
          if (x % 2 == 1 && y % 2 == 1) type = 'node';
          if (x % 2 == 0 && y % 2 == 0) type = 'virtual';

          let content =
            type == 'node'
              ? `
              <div class="node-background"><div class="empty-node"></div></div>
              <div class="animal-holder resource-holder-update"></div>
              <div class="stable-holder"></div>`
              : '';
          if (type == 'edge') content = '<div class="animal-holder resource-holder-update"></div>';

          html += `<div data-x='${x}' data-y='${y}' class="board-cell cell-${type}">${content}</div>`;
        }
      }
      html += `
            </div>
          </div>
          <div class="cards-wrapper" id="cards-wrapper-${player.id}"></div>
        </div>
      </div>
    </div>
      `;

      return html;
    },

    /**
     * Make cards selectable
     */
    promptPlayerBoardZones(zones, min, max, callback, callbackCheck = null, options = {}) {
      let selectedZones = [];
      let select = (pos) => {
        dojo.addClass(this.getCell(pos), 'selected');
      };
      let unselect = (pos) => {
        dojo.removeClass(this.getCell(pos), 'selected');
      };

      let onClickZone = (pos) => {
        // Specific case where asked to only point out one zone
        // TODO : might add a "confirm" boolean param to overwrite this behavior when needed
        if (min == 1 && max == null) {
          callback(pos);
          return;
        }

        // Toggle element
        let i = selectedZones.findIndex((p) => p == pos);
        if (i == -1) {
          select(pos);
          selectedZones.push(pos);
        } else {
          unselect(pos);
          selectedZones.splice(i, 1);
        }

        // Add clear button
        dojo.destroy('btnClearZoneSelection');
        dojo.destroy('btnConfirmZoneSelection');
        if (selectedZones.length > 0) {
          this.addSecondaryActionButton('btnClearZoneSelection', _('Clear'), () => {
            selectedZones.forEach(unselect);
            selectedZones = [];
            dojo.destroy('btnClearZoneSelection');
            dojo.destroy('btnConfirmZoneSelection');
          });
        }

        // Add confirm button
        if (
          selectedZones.length >= min &&
          selectedZones.length <= max &&
          (callbackCheck == null || callbackCheck(selectedZones))
        ) {
          this.addPrimaryActionButton('btnConfirmZoneSelection', _('Confirm'), () => {
            callback(selectedZones);
          });
        }
      };

      // Attach event to zones
      zones.forEach((pos) => {
        this.onClick(this.getCell(pos), () => onClickZone(pos));
      });

      // Optional: show Confirm immediately when empty selection is allowed.
      // This is only used by stables so players can build "nothing" (for B85_FarmHand).
      if (options.showConfirmWhenEmpty && min === 0) {
        dojo.destroy('btnClearZoneSelection');
        dojo.destroy('btnConfirmZoneSelection');

        if (callbackCheck == null || callbackCheck([])) {
          this.addPrimaryActionButton('btnConfirmZoneSelection', _('Confirm'), () => {
            callback([]);
          });
        }
      }

    },

    /**
     * Flip a meeple (field, room) onto a cell
     */
    flipOnCell(meeple) {
      let target = this.getCell(meeple).querySelector('.node-background').firstChild;
      return this.flipAndReplace(target, this.tplMeeple(meeple));
    },

    /**
     * Adding a field to the board
     */
    notif_plow(n) {
      debug('Notif: adding a field ', n);
      let meeple = n.args.field;
      this.flipOnCell(meeple);
    },

    /**
     * Adding rooms to the board
     */
    notif_construct(n) {
      debug('Notif: adding rooms ', n);
      n.args.rooms.forEach((meeple) => {
        this.flipOnCell(meeple);
        this.getCell(meeple).querySelector('.node-background').classList.add('containsRoom');
      });
    },

    /**
     * Renovating rooms on the board
     */
    notif_renovate(n) {
      debug('Notif: renovating rooms', n);
      n.args.rooms.forEach((meeple) => {
        this.flipAndReplace('meeple-' + meeple.oldId, this.tplMeeple(meeple));
      });
    },

    /**
     * Adding fences to the board
     */
    notif_addFences(n) {
      debug('Notif: adding fences ', n);

      // Update directions of fence
      n.args.fences.forEach((fence, i) => {
        let className = 'fence-' + (fence.x % 2 == 1 ? 'hor' : 'ver');
        const isWoodPalisade = parseInt(fence.state, 10) === 10;
        const node = $('meeple-' + fence.id);
        if (node) {
          dojo.removeClass(node, 'fence-hor fence-ver fence-palisade');
          dojo.addClass(node, className);
          if (isWoodPalisade) {
            dojo.addClass(node, 'fence-palisade');
          }
        }
      });

      const actorPid = n.args.player_id ?? n.args.player?.id ?? null;
      const woodReserve = actorPid != null ? `reserve_${actorPid}_wood` : null;
      const canAnimateFromWoodReserve = woodReserve != null && $(woodReserve) != null;

      // Slide them
      let mult = this.getAnimationSpeedMultiplier();
      this.slideResources(n.args.fences, (fence, i) => {
        const cfg = {
          target: this.getCell(fence),
          delay: Math.round(200 * i * mult),
        };

        const isWoodPalisade = parseInt(fence.state, 10) === 10;
        if (isWoodPalisade && canAnimateFromWoodReserve) {
          cfg.from = woodReserve;
        }

        return cfg;
      });
    },

    /**
     * Adding stables to the board
     */
    notif_addStables(n) {
      debug('Notif: adding stables', n);
      this.slideResources(n.args.stables, {});
    },

    notif_farmHandStable(n) {
      debug('Notif: farmHandStable', n);

      const playerId = n.args.playerId;
      const pos = n.args.farmHandStable;
      const stableId = n.args.stableId;

      const overlay = this.addFarmHandStable(playerId, pos, true);

      const removeReserveMeepleAndUpdate = () => {
        const el = stableId ? $('meeple-' + stableId) : null;
        if (el) el.remove();

        this.updateResourcesHolders(false);
      };

      // Fast mode or instant speed: no real animation to wait for
      if (this.isFastMode() || this.isInstantSpeed() || !overlay) {
        removeReserveMeepleAndUpdate();
        return;
      }

      // Wait for the overlay move to finish, then update reserve counter
      let mult = this.getAnimationSpeedMultiplier();
      const cleanup = () => {
        overlay.removeEventListener('transitionend', cleanup);
        removeReserveMeepleAndUpdate();
      };

      overlay.addEventListener('transitionend', cleanup, { once: true });

      // Fallback: if transitionend doesn't fire for any reason
      window.setTimeout(() => {
        if (overlay) {
          overlay.removeEventListener('transitionend', cleanup);
        }
        removeReserveMeepleAndUpdate();
      }, Math.round(900 * mult));
    },

    notif_farmHandStableReturned(n) {
      debug('Notif: farmHandStableReturned', n);
      this.animateFarmHandStableToReserve(n.args.playerId, n.args.topLeft, true);
    },

    /**
     * B85 Farm Hand
     * Draw the special Farm Hand stable at the centre of a 2x2 field block.
     */
    addFarmHandStable(playerId, topLeft, animate = false) {
      if (!topLeft) return null;

      const board = document.getElementById('board-' + playerId);
      if (!board) return null;

      // Ensure absolute children position relative to the board
      const boardStyle = window.getComputedStyle(board);
      if (boardStyle.position === 'static') {
        board.style.position = 'relative';
      }

      const centrePos = { x: topLeft.x + 1, y: topLeft.y + 1, pId: playerId };
      const centreCell = this.getCell(centrePos, playerId);
      if (!centreCell) return null;

      // Remove any previous Farm Hand overlay so we only ever have one
      const existing = board.querySelector('#farmhand-stable-' + playerId);
      if (existing) existing.remove();

      const color = this.getPlayerColor(playerId);
      const html =
        `<div id="farmhand-stable-${playerId}"
              class="agricola-meeple meeple-stable farmhand-stable"
              data-color="${color}" data-player-id="${playerId}"></div>`;

      const stable = dojo.place(html, board);

      const boardRect = board.getBoundingClientRect();
      const centreRect = centreCell.getBoundingClientRect();

      const scaleX = boardRect.width / board.offsetWidth || 1;
      const scaleY = boardRect.height / board.offsetHeight || 1;

      const centreCx = (centreRect.left + centreRect.right) / 2;
      const centreCy = (centreRect.top + centreRect.bottom) / 2;

      const finalLeft = (centreCx - boardRect.left) / scaleX;
      const finalTop  = (centreCy - boardRect.top) / scaleY;

      stable.style.position = 'absolute';
      stable.style.left = finalLeft + 'px';
      stable.style.top = finalTop + 'px';
      stable.style.transform = 'translate(-50%, -50%)';
      stable.style.pointerEvents = 'none';
      stable.style.zIndex = '10';

      if (!animate) return stable;

      const reserve =
        document.getElementById('reserve_' + playerId + '_stable') ||
        document.getElementById('reserve-' + playerId);

      if (!reserve) return stable;

      const reserveRect = reserve.getBoundingClientRect();
      const reserveCx = (reserveRect.left + reserveRect.right) / 2;
      const reserveCy = (reserveRect.top + reserveRect.bottom) / 2;

      const startLeft = (reserveCx - boardRect.left) / scaleX;
      const startTop  = (reserveCy - boardRect.top) / scaleY;

      stable.style.transition = 'none';
      stable.style.left = startLeft + 'px';
      stable.style.top = startTop + 'px';

      let dur = Math.round(800 * this.getAnimationSpeedMultiplier());
      requestAnimationFrame(() => {
        stable.getBoundingClientRect(); // force reflow
        stable.style.transition = `left ${dur}ms ease, top ${dur}ms ease`;
        stable.style.left = finalLeft + 'px';
        stable.style.top = finalTop + 'px';
      });

      return stable;
    },

    /**
     * B85 Farm Hand
     * Animate removal of the Farm Hand stable from the board back to the stable reserve.
     */
    animateFarmHandStableToReserve(playerId, topLeft, animate = false) {
      const board = document.getElementById('board-' + playerId);
      if (!board) return;

      // If we don't know where it was, just remove the DOM overlay.
      const existing = board.querySelector('#farmhand-stable-' + playerId);
      if (!existing) return;

      if (!animate) {
        existing.remove();
        return;
      }

      const reserve =
        document.getElementById('reserve_' + playerId + '_stable') ||
        document.getElementById('reserve-' + playerId);

      if (!reserve) {
        existing.remove();
        return;
      }

      // Compute start position (board centre point) using the same logic as addFarmHandStable
      if (!topLeft) {
        // No coordinates: remove immediately (can't animate accurately)
        existing.remove();
        return;
      }

      const centrePos = { x: topLeft.x + 1, y: topLeft.y + 1, pId: playerId };
      const centreCell = this.getCell(centrePos, playerId);
      if (!centreCell) {
        existing.remove();
        return;
      }

      const boardStyle = window.getComputedStyle(board);
      if (boardStyle.position === 'static') board.style.position = 'relative';

      const boardRect = board.getBoundingClientRect();
      const centreRect = centreCell.getBoundingClientRect();

      const scaleX = boardRect.width / board.offsetWidth || 1;
      const scaleY = boardRect.height / board.offsetHeight || 1;

      const centreCx = (centreRect.left + centreRect.right) / 2;
      const centreCy = (centreRect.top + centreRect.bottom) / 2;

      const startLeft = (centreCx - boardRect.left) / scaleX;
      const startTop  = (centreCy - boardRect.top) / scaleY;

      // Compute end position (reserve centre) in the same coordinate space
      const reserveRect = reserve.getBoundingClientRect();
      const reserveCx = (reserveRect.left + reserveRect.right) / 2;
      const reserveCy = (reserveRect.top + reserveRect.bottom) / 2;

      const endLeft = (reserveCx - boardRect.left) / scaleX;
      const endTop  = (reserveCy - boardRect.top) / scaleY;

      // In instant speed, just remove immediately
      if (this.isInstantSpeed()) {
        existing.remove();
        return;
      }

      // Create a moving clone so we can remove the real overlay immediately
      const moving = existing.cloneNode(true);
      moving.id = 'farmhand-stable-' + playerId + '-moving';
      moving.style.position = 'absolute';
      moving.style.left = startLeft + 'px';
      moving.style.top = startTop + 'px';
      moving.style.transform = 'translate(-50%, -50%)';
      moving.style.pointerEvents = 'none';
      moving.style.zIndex = '5000';

      // Remove the real one immediately (UI state correctness)
      existing.remove();
      board.appendChild(moving);

      // Animate to reserve
      let dur = Math.round(800 * this.getAnimationSpeedMultiplier());
      moving.style.transition = 'none';
      requestAnimationFrame(() => {
        moving.getBoundingClientRect(); // force reflow
        moving.style.transition = `left ${dur}ms ease, top ${dur}ms ease`;
        moving.style.left = endLeft + 'px';
        moving.style.top = endTop + 'px';
      });

      // Cleanup
      const cleanup = () => {
        if (moving && moving.parentNode) moving.remove();
      };
      moving.addEventListener('transitionend', cleanup, { once: true });
      // Fallback in case transitionend doesn't fire
      window.setTimeout(cleanup, Math.round(900 * this.getAnimationSpeedMultiplier()));
    },
  });
});
