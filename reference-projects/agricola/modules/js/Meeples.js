define(['dojo', 'dojo/_base/declare'], (dojo, declare) => {
  const MEEPLES = [
    'WOOD',
    'CLAY',
    'REED',
    'STONE',
    'FOOD',
    'GRAIN',
    'VEGETABLE',
    'SHEEP',
    'PIG',
    'CATTLE',
    'SCORE',
    'ARROW',
    'ARROW-1X',
    'ARROW-2X',
    'FENCE',
    'MAJOR',
    'MINOR',
    'OCCUPATION',
    'FIELD',
    'SOW',
    'BREAD',
    'FIRST',
    'CHILD',
    'CHILD_FREE',
    'ROOM_WOOD',
    'ROOM_CLAY',
    'ROOM_STONE',
    'BARN',
    'UPGRADE',
    'COOK',
    'FARMER',
    'BEGGING',
    'REORGANIZE',
    'PASTURE',
    'EMPTY',
    'STABLE',
    'WOODPLUS',
    'FOODPLUS',
    'FIELDPLUS',
    'VEGETABLEPLUS',
    'GRAIN_VEG_STACK',
    'BAKE'
  ];

  const PERSONAL_RESOURCES = ['farmer', 'fence', 'stable'];

  return declare('agricola.meeples', null, {
    setupMeeples() {
      // This function is refreshUI compatible
      let meepleIds = this.gamedatas.meeples.map((meeple) => {
        if (!$('meeple-' + meeple.id)) {
          this.addMeeple(meeple);
        }

        let o = $('meeple-' + meeple.id);
        const displayType = this.getMeepleDisplayType(meeple);
        if (o.dataset.type != displayType) {
          o.classList.remove(`meeple-${o.dataset.type}`);
          o.classList.add(`meeple-${displayType}`);
          o.dataset.type = displayType;
        }

        let container = this.getMeepleContainer(meeple);
        if (o.parentNode != $(container)) {
          dojo.place(o, container);
        }

        return meeple.id;
      });
      document.querySelectorAll('.agricola-meeple[id^="meeple-"]').forEach((oMeeple) => {
        if (!meepleIds.includes(parseInt(oMeeple.getAttribute('data-id')))) {
          dojo.destroy(oMeeple);
        }
      });
      document.querySelectorAll('.node-background').forEach((node) => {
        if (node.childNodes.length == 0) {
          dojo.place('<div class="empty-node"></div>', node);
          node.classList.remove('containsRoom');
        }
      });
      this.updateResourcesHolders(false, false);
    },

    updateResourcesHolders(preAnimation, anim = true) {
      dojo.query('.resource-holder-update').forEach((container) => {
        let n = container.childNodes.length;
        dojo.attr(container, 'data-n', n);
        if (container.parentNode.classList.contains('future-resource-wrapper')) {
          this.updateFutureMeeplesDescription(container.parentNode, n);
        }
      });
      this.updatePlayersCounters(anim);
      this.updateDropZonesStatus(preAnimation);
      this.fixWorkshopAssistantPairs();
      if (typeof this.updateAdditionalActionLockUI === 'function') {
        this.updateAdditionalActionLockUI();
      }

    },

    fixWorkshopAssistantPairs() {
      dojo.query('.player-card[data-id="C146_WorkshopAssistant"] .resource-holder').forEach((holder) => {
        // Unwrap if already wrapped (important when rerunning after updates)
        dojo.query('> .subholder', holder).forEach((sub) => {
          while (sub.firstChild) holder.insertBefore(sub.firstChild, sub);
          sub.remove();
        });

        const meeples = dojo.query('> .agricola-meeple', holder);
        if (meeples.length < 2) return;

        const groups = {};
        meeples.forEach((m) => {
          const x = dojo.attr(m, 'data-x');
          if (x === '' || x == null) return;
          (groups[x] ||= []).push(m);
        });

        const xs = Object.keys(groups)
          .map((v) => parseInt(v, 10))
          .sort((a, b) => a - b);

        xs.forEach((x) => {
          groups[x].sort((m1, m2) => {
            const y1 = parseInt(dojo.attr(m1, 'data-y') || '0', 10);
            const y2 = parseInt(dojo.attr(m2, 'data-y') || '0', 10);
            return y1 - y2;
          });

          const wrap = dojo.create('div', { class: 'subholder', 'data-x': '' + x });
          holder.appendChild(wrap);
          groups[x].forEach((m) => wrap.appendChild(m));
        });

        dojo.addClass(holder, 'wa-pairs');
      });
    },

    updateFutureMeeplesDescription(container, n) {
      container.dataset.n = n;
      let descContainer = container.querySelector('.future-resource-description');
      descContainer.innerHTML = '';
      if (n == 0) return;

      let meepleCount = {};
      const realMeeples = [...container.querySelectorAll('.agricola-meeple[id^="meeple-"]')];

      realMeeples.forEach((meeple) => {
        let type = meeple.dataset.type;
        if (!meepleCount[type]) meepleCount[type] = 0;
        meepleCount[type]++;
      });

      const colors = new Set(
        realMeeples
          .map(m => m.dataset.color)
          .filter(c => c !== undefined && c !== null && c !== '')
      );
      const sharedColor =
        colors.size === 1 ? [...colors][0] :
        (container.dataset && container.dataset.color) ? container.dataset.color : null;

      let desc = this.formatResourceArray(meepleCount, true, '<br>');
      descContainer.innerHTML = desc;

      if (sharedColor) {
        descContainer.querySelectorAll('.agricola-meeple').forEach((m) => {
          m.dataset.color = sharedColor;
        });
      }
    },

    localUpdateResourcesHolders(meeple, leaving) {
      let parent = meeple.parentNode;
      if (parent.classList.contains('resource-holder-update')) {
        let n = parseInt(parent.getAttribute('data-n') || 0);
        n = n + (leaving ? -1 : 1);
        parent.setAttribute('data-n', n);

        if (parent.parentNode.classList.contains('future-resource-wrapper')) {
          if (!parent.parentNode.dataset.color && meeple.dataset.color) {
            parent.parentNode.dataset.color = meeple.dataset.color;
          }
          this.updateFutureMeeplesDescription(parent.parentNode, n);
        }
      } else if (parent.classList.contains('reserve')) {
        let t = parent.id.split('_');
        if (PERSONAL_RESOURCES.includes(t[2])) {
          leaving = !leaving;
        }

        let n = parseInt(parent.parentNode.getAttribute('data-n') || 0);
        n += leaving ? -1 : 1;
        this._playerCounters[t[1]][t[2]].toValue(n);
        parent.parentNode.setAttribute('data-n', n);
      }

      let type = meeple.getAttribute('data-type');
      this.localUpdateResourcesHoldersType(type);
    },

    localUpdateResourcesHoldersType(type) {
      if (['sheep', 'pig', 'cattle'].includes(type)) {
        this.updateAnimalsPlayerCounters(type);
        this.updateDropZonesStatus();
      } else if (type == 'farmer') {
        this.updateFarmersPlayerCounters();
      }
    },

    addMeeple(meeple, location = null) {
      if ($('meeple-' + meeple.id)) return;
      this.place('tplMeeple', meeple, location == null ? this.getMeepleContainer(meeple) : location);
    },

    // Some meeple types render as a smaller "icon" variant when shown off-board
    // (on future round spaces, card accumulators, etc). Resolved without mutating
    // meeple.type so that refreshUI — which replaces gamedatas.meeples — stays idempotent.
    getMeepleDisplayType(meeple) {
      if (meeple.location == 'board') return meeple.type;
      if (meeple.type == 'field') return 'field_icon';
      if (meeple.type == 'roomStone') return 'room_stone';
      return meeple.type;
    },

    tplMeeple(meeple) {
      const colorOwnerPid = (meeple.colorPid !== undefined && meeple.colorPid !== null) ? meeple.colorPid : meeple.pId;
      let color = colorOwnerPid == 0 ? colorOwnerPid : this.getPlayerColor(colorOwnerPid);
      let className = '';
      if (meeple.type == 'fence') {
        className = 'fence-' + (meeple.x % 2 == 1 ? 'hor' : 'ver');
        if (parseInt(meeple.state, 10) === 10) {
          className += ' fence-palisade';
        }
      }
      if (meeple.type == 'farmer') {
        className = meeple.state == 1 ? 'child' : '';
      }
      const displayType = this.getMeepleDisplayType(meeple);

      return `<div class="agricola-meeple meeple-${displayType} ${className}" id="meeple-${meeple.id}" data-id="${meeple.id}" data-color="${color}" data-type="${displayType}" data-x="${meeple.x ?? ''}" data-y="${meeple.y ?? ''}"></div>`;
    },

    getMeepleContainer(meeple) {
      if (meeple.location == 'board') {
        if (meeple.type == 'farmer' && meeple.x == null && meeple.y == null) {
          // TODO : remove !
          meeple.x = 1;
          meeple.y = 5;
        }
        let container = this.getCell(meeple);

        if (['vegetable', 'grain'].includes(meeple.type)) {
          let field = container.querySelector('.meeple-field');
          if (field) {
            container = this.getSeedContainer({ id: field.getAttribute('data-id') });
          } else {
            container = this.getSeedContainer(meeple);
          }
        }

        if (['sheep', 'pig', 'cattle'].includes(meeple.type)) {
          container = container.querySelector('.animal-holder');
        }

        if (['stable'].includes(meeple.type)) {
          container = container.querySelector('.stable-holder');
        }

        if (['field', 'roomWood', 'roomClay', 'roomStone'].includes(meeple.type)) {
          container = container.querySelector('.node-background');
          dojo.empty(container);
          dojo.addClass(container, 'containsRoom');
        }

        return container;
      } else if (meeple.location == 'reserve') {
        let reserve = $(`reserve_${meeple.pId}_${meeple.type}`);
        if (reserve == null) {
          reserve = 'reserve-' + meeple.pId;
        }
        return reserve;
      } else if (meeple.location.substr(0, 4) == 'turn') {
        return $(meeple.location).querySelector('[data-pid="' + meeple.pId + '"] .resource-holder-update');
      } else if (meeple.type == 'score' && meeple.location == 'panelScore') {
        return $('player_score_' + meeple.pId);
      } else if ($(meeple.location)) {
        let holder = meeple.type == 'farmer' ? 'farmer' : 'resource';
        if (meeple.type == 'score') {
          return document.querySelector('#' + meeple.location + ' .card-bonus-vp-counter');
        }

        if (meeple.location == 'D75_WoodField' || meeple.location == 'E80_RockGarden') {
          return document.querySelector(`#${meeple.location} .resource-holder .subholder[data-x="${meeple.x}"]`);
        }

        return document.querySelector('#' + meeple.location + ' .' + holder + '-holder');
      }

      console.error('Trying to get container of a meeple', meeple);
      return 'game_play_area';
    },

    /**
     * Placing a farmer on an action card
     */
    notif_placeFarmer(n) {
      debug('Notif: place farmer ', n);
      let meeple = {
        id: n.args.farmer,
        location: n.args.card.id,
        type: 'farmer',
      };
      this.slideResources([meeple], {
        duration: 1000,
      });
    },

    /**
     * Wrap the sliding animations with a call to updateResourcesHolders() before and after the sliding is done
     */
    slideResources(meeples, configFn, syncNotif = true, updateHoldersAtEachMeeples = true, skipPersonalLocalUpdates = false) {
      meeples = Array.isArray(meeples) ? meeples : [];

      if (meeples.length === 0) {
        if (syncNotif) {
          this.notifqueue.setSynchronousDuration(this.isFastMode() ? 0 : 10);
        }
        return null;
      }

      const shouldLocalUpdate = (resource) =>
        updateHoldersAtEachMeeples && !(skipPersonalLocalUpdates && PERSONAL_RESOURCES.includes(resource.type));
      // Snapshot counters before any slides for toast calculation (only first call in a burst)
      if (this.isInstantSpeed() && !this.isFastMode() && !this._toastSnapshot) {
        this._toastSnapshot = this.snapshotPlayerCounters();
      }
      let fakeId = -1; // Used for virtual meeple that will get destroyed after animation (eg SCORE)
      let promises = meeples.map((resource, i) => {
        // Get config for this slide
        let config = typeof configFn === 'function' ? configFn(resource, i) : Object.assign({}, configFn);
        if (resource.destroy) {
          resource.id = fakeId--;
          config.destroy = true;
        }

        // Default delay if not specified (scale default by animation speed)
        let delay = config.delay ? config.delay : Math.round(100 * i * this.getAnimationSpeedMultiplier());
        config.delay = 0;
        // Use meepleContainer if target not specified
        let target = config.target ? config.target : this.getMeepleContainer(resource);

        // Slide it
        let slideIt = () => {
          // Create meeple if needed
          if (!$('meeple-' + resource.id)) {
            this.addMeeple(resource);
          } else {
            // Sync DOM type with server type (e.g. vegetableplus→vegetable after fixMeepleType)
            let el = $('meeple-' + resource.id);
            let oldType = el.getAttribute('data-type');
            if (oldType && oldType !== resource.type) {
              el.classList.remove('meeple-' + oldType);
              el.classList.add('meeple-' + resource.type);
              el.setAttribute('data-type', resource.type);
            }
            // Sync data-color so player-colored meeples (stables, Midnight Fencer fences) stay correct during animations
            if (resource.pId || resource.colorPid) {
              const colorOwnerPid = (resource.colorPid !== undefined && resource.colorPid !== null) ? resource.colorPid : resource.pId;
              let newColor = colorOwnerPid == 0 ? '0' : this.getPlayerColor(colorOwnerPid);
              if (el.getAttribute('data-color') !== newColor) {
                el.setAttribute('data-color', newColor);
              }
            }
            if (shouldLocalUpdate(resource)) {
              this.localUpdateResourcesHolders(el, true);
            }
          }

          // Slide it
          return this.slide('meeple-' + resource.id, target, config);
        };

        // Update locally
        let updateCounters = () => {
          if (shouldLocalUpdate(resource)) {
            if ($('meeple-' + resource.id)) {
              this.localUpdateResourcesHolders($('meeple-' + resource.id), false);
            } else {
              this.localUpdateResourcesHoldersType(resource.type);
            }
          }

          if (resource.type == 'score') {
            let counter = document.querySelector('#' + resource.location + ' .card-bonus-vp-counter');
            if (counter) {
              let current = counter.innerHTML == '' ? 0 : parseInt(counter.innerHTML);
              current++;
              counter.innerHTML = current;
              this.refreshCardTooltipBonusVp(resource.location, current);
            }
          }
        };

        if (this.isFastMode() || this.isInstantSpeed()) {
          slideIt();
          updateCounters();
          return null;
        } else {
          return this.wait(delay - 10)
            .then(slideIt)
            .then(updateCounters);
        }
      });

      // Update counters of receiving holders once all the promises are resolved
      let finalCounterUpdate = () => {
        if (!updateHoldersAtEachMeeples) {
          this.updateResourcesHolders(false);
        }

        window.requestAnimationFrame(() => this.fixWorkshopAssistantPairs());
        if (typeof this.updateAdditionalActionLockUI === 'function') {
          this.updateAdditionalActionLockUI();
        }

        if (syncNotif) {
          this.notifqueue.setSynchronousDuration(this.isFastMode() ? 0 : 10);
        }
      };

      if (this.isFastMode() || this.isInstantSpeed()) {
        finalCounterUpdate();
        if (this._toastSnapshot) {
          clearTimeout(this._toastDebounce);
          this._toastDebounce = setTimeout(() => {
            this.showResourceToasts(this._toastSnapshot);
            this._toastSnapshot = null;
          }, 50);
        }
        return;
      } else
        return Promise.all(promises)
          .then(() => this.wait(10))
          .then(finalCounterUpdate);
    },

    _getDisplayedCount(playerId, res) {
      let ANIMALS = ['sheep', 'pig', 'cattle'];
      if (ANIMALS.includes(res)) {
        let el = $('board_resource_' + playerId + '_' + res);
        return el ? parseInt(el.innerHTML) || 0 : 0;
      }
      let counter = this._playerCounters[playerId] && this._playerCounters[playerId][res];
      return counter ? counter.getValue() : 0;
    },

    snapshotPlayerCounters() {
      let snapshot = {};
      let TOAST_TYPES = ['wood', 'clay', 'reed', 'stone', 'food', 'grain', 'vegetable', 'sheep', 'pig', 'cattle'];
      this.forEachPlayer((player) => {
        TOAST_TYPES.forEach((res) => {
          snapshot[player.id + '_' + res] = this._getDisplayedCount(player.id, res);
        });
      });
      return snapshot;
    },

    showResourceToasts(snapshotBefore) {
      let TOAST_TYPES = ['wood', 'clay', 'reed', 'stone', 'food', 'grain', 'vegetable', 'sheep', 'pig', 'cattle'];
      let ANIMALS = ['sheep', 'pig', 'cattle'];
      this.forEachPlayer((player) => {
        TOAST_TYPES.forEach((res) => {
          let key = player.id + '_' + res;
          let before = snapshotBefore[key] || 0;
          let after = this._getDisplayedCount(player.id, res);
          let delta = after - before;
          if (delta === 0) return;

          let isAnimal = ANIMALS.includes(res);
          let counterId = (isAnimal ? 'board_resource_' : 'resource_') + player.id + '_' + res;
          let counterEl = $(counterId);
          if (!counterEl) return;
          let container = counterEl.closest('.player-resource');
          if (!container) return;

          let sign = delta > 0 ? '+' : '';
          let toast = document.createElement('div');
          toast.className = 'resource-toast' + (delta > 0 ? ' resource-toast-gain' : ' resource-toast-loss');
          toast.textContent = sign + delta;
          container.appendChild(toast);
          requestAnimationFrame(() => {
            toast.classList.add('resource-toast-animate');
            toast.addEventListener('animationend', () => toast.remove());
          });
        });
      });
    },

    /**
     * Accumulation
     */
    notif_accumulation(n) {
      debug('Notif: accumulation', n);
      this.slideResources(n.args.resources, (meeple) => ({
        from: document.querySelector('#' + meeple.location + ' .action-desc'),
      }));
    },

    /**
     * Collect resources from a card
     */
    notif_collectResources(n) {
      debug('Notif: collecting resources', n);

      const resources = n.args.resources ?? [];
      if (resources.length === 0) {
        this.notifqueue.setSynchronousDuration(this.isFastMode() ? 0 : 10);
        return;
      }

      // Normal resources still slide as usual
      const toSlide = resources.filter(r => !r.noPanelSlide);

      // Special case (B85_FarmHand): items we want to affect counters but NOT animate
      const noSlide = resources.filter(r => r.noPanelSlide);

      if (toSlide.length) {
        this.slideResources(toSlide, {});
      }

      if (noSlide.length) {
        noSlide.forEach((r) => {
          // target holder container the framework expects
          const target = this.getMeepleContainer(r);

          // create if missing
          if (!$('meeple-' + r.id)) {
            this.addMeeple(r);
          }

          const el = $('meeple-' + r.id);
          if (el && target) {
            // Put it directly in the target without animation
            if (el.parentNode !== target) {
              target.appendChild(el);
            }
          }

          // Update counters for this type/holder
          this.localUpdateResourcesHoldersType(r.type);
        });

        // Also do the "final" holders refresh
        this.updateResourcesHolders(false);

        // And advance notifqueue similarly to slideResources
        this.notifqueue.setSynchronousDuration(this.isFastMode() ? 0 : 10);
      }
    },

    /**
     * Collect resources on a round space
     */
    notif_collectRoundResources(n) {
      debug('Notif: collecting resources', n);
      let mult = this.getAnimationSpeedMultiplier();
      this.slideResources(n.args.resources, {
        delay: Math.round(50 * mult),
        duration: 300, // duration auto-scaled by slide()
      });
    },

    /**
     * Gain resources (create them from the reserve)
     */
    notif_gainResources(n) {
      debug('Notif: gain resoures', n);
      this.slideResources(n.args.resources, {
        from: n.args.cardId ? n.args.cardId : 'page-title',
      });
    },

    /**
     * Pay resources for an action
     */

    notif_payResources(n) {
      debug('Notif: paying resoures', n);

      const resources = n.args.resources ?? [];
      const skipPaymentAnimation = n.args.sourceInfo?.skipPaymentAnimation === true;
      if (skipPaymentAnimation) {
        resources.forEach((resource) => {
          const node = $('meeple-' + resource.id);
          if (node) {
            dojo.destroy(node);
          }
        });

        this.updatePlayersCounters(false);
        this.notifqueue.setSynchronousDuration(this.isFastMode() ? 0 : 10);
        return;
      }

      const p = this.slideResources(
        resources,
        () => ({ target: 'page-title', destroy: true }),
        true,
        true,
        true
      );

      if (p && typeof p.then === 'function') {
        p.then(() => this.updatePlayersCounters(false));
      } else {
        this.updatePlayersCounters(false);
      }
    },

    notif_silentKill(n) {
      debug('Notif: silenKill', n);

      const p = this.slideResources(
        n.args.resources,
        () => ({ duration: 10, destroy: true }),
        true,
        true,
        true
      );

      const recalcHolders = () => {
        dojo.query('.resource-holder-update').forEach((container) => {
          dojo.attr(container, 'data-n', container.childNodes.length);
        });
        this.updatePlayersCounters(false);
      };

      if (p && typeof p.then === 'function') {
        p.then(recalcHolders);
      } else {
        recalcHolders();
      }
    },

    notif_silentDestroy(n) {
      debug('Notif: silent destroy', n);
      n.args.resources.forEach((meeple) => dojo.destroy('meeple-' + meeple.id));
      dojo.query('.resource-holder-update').forEach((container) => {
        dojo.attr(container, 'data-n', container.childNodes.length);
      });
    },

    /**
     * A player picked the firstplayer token
     */
    notif_firstPlayer(n) {
      debug('Notif: moving first player token', n);
      this.slide('meeple-' + n.args.meepleId, 'reserve_' + n.args.player_id + '_firstPlayer', {});
    },

    /**
     * All farmers return home
     */
    notif_returnHome(n) {
      debug('Notif: returning home', n);
      this.slideResources(n.args.farmers, {
        delay: 0,
        duration: 1200,
      });
    },

    /**
     * Return one farmer
     */
    notif_returnHomeOne(n) {
      debug('Notif: returning home one', n);
      let meeple = {
        id: n.args.farmer,
        type: 'farmer',
      };
      this.slideResources([meeple], {
        delay: 0,
        duration: 1200,
      });
    },

    /**
     * An extra farmer makes popin
     */
    notif_growFamily(n) {
      debug('Notif: grow family', n);
      this.slideResources([n.args.meeple], {});
      dojo.addClass('meeple-' + n.args.meeple.id, 'child');
    },

    /**
     * Children becomes adult
     */
    notif_growChildren(n) {
      debug('Notif: growing children', n);
      n.args.ids.forEach((mId) => dojo.removeClass('meeple-' + mId, 'child'));
    },

    /**
     * Some meeples are placed on future action cards
     */
    notif_placeMeeplesForFuture(n) {
      debug('Notif: placing meeples for future', n);
      this.slideResources(n.args.meeples, { from: 'page-title' });
    },

    /**
     * Some meeples are placed on action spaces
     */
    notif_placeMeeplesFromSupply(n) {
      debug('Notif: placing meeples from supply on action space(s)', n);
      this.slideResources(n.args.meeples, (meeple) => ({
        from: 'reserve_' + n.args.player_id + '_' + meeple.type,
      }));
    },

    /**
     * A player harvest crops
     */
    notif_harvestCrop(n) {
      debug('Notif: harvesting crops', n);
      this.slideResources(n.args.resources, {});
    },

    /**
     * Update card stats
     */
    notif_updateCardStats(n) {
      debug('Notif: update card stats', n);

      if (n.args.cardId != null && n.args.cardStats != null) {
        let o = $(n.args.cardId);
        if (this._cardStorage?.[n.args.cardId]) {
          this._cardStorage[n.args.cardId].stats = n.args.cardStats;
        }

        let cardData = this.gamedatas.playerCards.find((c) => c.id === n.args.cardId)
          || (this.gamedatas.players[this.player_id]?.hand || []).find((c) => c.id === n.args.cardId);
        if (cardData) {
          cardData.stats = n.args.cardStats;
        }

        if (o) {
          o.stats = n.args.cardStats;
          this.loadSaveCard(o);
        }
      }
    },

    /**
     * Place card infobox
     */
    notif_placeInfobox(n) {
      debug('Notif: place card info box', n);

      if (n.args.cardId != null) {
        let o = $(n.args.cardId);
        if (o) {
          o.infobox = n.args.string;
          this.loadSaveCard(o);
          this.placeInfobox(o);
        }
      }
    },

    /**
     * Update card infobox
     */
    notif_updateInfobox(n) {
      debug('Notif: update card info box', n);

      if (n.args.cardId != null) {
        let o = $(n.args.cardId);
        if (o) {
          o.infobox = n.args.string;
          this.loadSaveCard(o);
          if (n.args.string == null) {
            this.removeInfobox(o);
          } else {
            this.updateInfobox(o);
          }
        }
      }
    },

    /**
     * Mark a card that is usable during the harvest only
     */
    notif_markUsableExchange(n) {
      debug('Notif: marking usable harvest exchange', n);
      this.markUsableExchange(n.args.cardId);
    },

    /**
     * Unmark a card that is usable during the harvest only
     */
    notif_unmarkUsableExchange(n) {
      debug('Notif: unmarking usable harvest exchange', n);
      this.unmarkUsableExchange(n.args.cardId);
    },

    /**
     * Unmark all cards that are usable during the harvest only
     */
    notif_unmarkAllUsableExchanges(n) {
      debug('Notif: unmarking all usable harvest exchanges', n);
      this.unmarkAllUsableExchanges();
    },

    /**
     * Replace some expressions by corresponding html formating
     */
    formatStringMeeples(str) {
      // Drop the readable resource names the server appends for the archived log (see
      // Notifications::appendResourceNames); the live view shows the icon instead.
      str = str.replace(/<span class="log-res-name">[^<]*<\/span>/g, '');

      // This text icon are also board component, so we add the prefix _icon to distinguish them
      let conflictingNames = ['FENCE', 'FIELD', 'FARMER'];

      let jstpl_meeple = `
      <div class="meeple-container">
        <div class="agricola-meeple meeple-\${type}">
        </div>
      </div>
      `;
      MEEPLES.forEach((name) => {
        let newName = name.toLowerCase() + (conflictingNames.includes(name) ? '_icon' : '');
        str = str.replace(new RegExp('<' + name + '>', 'g'), this.format_string(jstpl_meeple, { type: newName }));
        str = str.replace(/\[([^\]]+)\]/gi, '<span class="text">$1</span>'); // Replace [my text] by <span clas="text">my text</span>
      });
      str = str.replace(/\{\{([^\}]+)\}\}/gi, '<div class="text-wrapper">$1</div>'); // Replace {{my wrapped text}} by <div clas="text-wrapper">my wrapped text</div>
      return str;
    },

    /**
     * Return a string corresponding to an array of resources
     * [
     *   resourceType => amount,
     *   ...
     * ]
     */
    formatResourceArray(resources, formatMeeples = true, separator = ',') {
      let formated = [];
      Object.keys(resources).forEach((type) => {
        if (!MEEPLES.includes(type.toUpperCase())) return;

        let v = resources[type];
        formated.push((v > 1 ? v : '') + '<' + type.toUpperCase() + '>');
      });
      let desc = formated.join(separator);
      return formatMeeples ? this.formatStringMeeples(desc) : desc;
    },

    /**
     * Format log strings
     *  @Override
     */
    format_string_recursive(log, args) {
      try {
        if (log && args && !args.processed) {
          args.processed = true;

          // Derive player color for coloring player-specific meeple icons (stables, etc.)
          let playerColor = args.playerColor
            ?? (args.player_id && this.gamedatas.players[args.player_id]
                ? this.gamedatas.players[args.player_id].color
                : null);

          // Representation of the class of a card
          if (args.resources_desc !== undefined) {
            args.resources_desc = this.formatStringMeeples(args.resources_desc);
            if (playerColor && !args.resources_desc.includes('data-color')) {
              args.resources_desc = `<span data-color="${playerColor}">${args.resources_desc}</span>`;
            }
          }
          if (args.resources2_desc !== undefined) {
            args.resources2_desc = this.formatStringMeeples(args.resources2_desc);
          }

          // Replace __str__ by italic wrapper
          log = log.replace(/__([^_]+)__/g, '<span class="action-card-name-reference">$1</span>');
        }
      } catch (e) {
        console.error(log, args, 'Exception thrown', e.stack);
      }

      return this.inherited(arguments);
    },
  });
});
