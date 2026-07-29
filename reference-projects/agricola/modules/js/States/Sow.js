define(['dojo', 'dojo/_base/declare'], (dojo, declare) => {
  const WOOD_FIELDS = ['D75_WoodField', 'D75_WoodField2', 'E68_CherryOrchard'];
  const STONE_FIELDS = ['E80_RockGarden', 'E80_RockGarden2', 'E80_RockGarden3'];

  function baseGroupFromUid(uid) {
    if (uid.startsWith('D75_WoodField')) return 'D75_WoodField';
    if (uid.startsWith('E80_RockGarden')) return 'E80_RockGarden';
    return uid; // normal board field = its own group
  }

  return declare('agricola.sow', null, {
    onEnteringStateSow(args) {
      this.gamedatas.gamestate.args.zones.forEach((zone) => {
        if (zone.type == 'fieldCard' && WOOD_FIELDS.includes(zone.id)) {
          this.createWoodFieldRadios(zone);
          return;
        }

        if (zone.type == 'fieldCard' && STONE_FIELDS.includes(zone.id)) {
          this.createStoneFieldRadios(zone);
          return;
        }

        zone.sid = zone.type == 'fieldCard' ? zone.id : zone.x + '-' + zone.y;
        this.place('tplSowSwitch', zone, this.getSowZoneContainer(zone));
        document.getElementsByName('switch-' + zone.sid).forEach((radio) => {
          dojo.connect(radio, 'change', () => this.updateSowSwitches());
        });
      });
      this.updateSowSwitches();
    },

    getSowZoneContainer(zone) {
      if (zone.type == 'field' || zone.type == 'pastureField') {
        return this.getCell(zone);
      } else {
        let elem = $(zone.id).querySelector('.player-card-field-cell');
        dojo.addClass(elem, 'active');
        return elem;
      }
    },

    forEachSowRadios(callback) {
      this.gamedatas.gamestate.args.zones.forEach((zone) => {
        document.getElementsByName('switch-' + zone.sid).forEach((radio) => {
          callback(radio, zone);
        });
      });
    },

    clearSowRadios() {
      dojo.query('.switch-wrapper').forEach(dojo.destroy);
      dojo.query('.player-card-field-cell').removeClass('active');
    },

    /**
     * Update sow radios depending on what is currently selected
     *  and toggle confirm button
     */
    updateSowSwitches() {
      // Compute selected crops
      let crops = { grain: 0, neutral: 0, vegetable: 0, wood: 0, stone: 0 };
      let selectedGroup = null;

      this.forEachSowRadios((radio, zone) => {
        if (radio.checked) {
          crops[radio.value]++;
          if (radio.value !== 'neutral' && selectedGroup == null) {
            selectedGroup = baseGroupFromUid(zone.uid); 
          }
        }
      });

      // Update disabled states
      let args = this.gamedatas.gamestate.args;
      this.forEachSowRadios((radio, zone) => {
        if (radio.value != 'neutral') {
          const outsideLockedGroup =
            args.max === 1 && selectedGroup && baseGroupFromUid(zone.uid) !== selectedGroup;

          radio.disabled =
            (!radio.checked && crops[radio.value] == args[radio.value]) ||
            (zone.constraints && zone.constraints != radio.value) ||
            outsideLockedGroup;
        }
      });

      // Update button
      dojo.destroy('btnConfirmSow');
      let total = crops.grain + crops.vegetable + (crops.wood > 0) + (crops.stone > 0);
      if (total > 0 && total <= this.gamedatas.gamestate.args.max) {
        this.addPrimaryActionButton('btnConfirmSow', _('Confirm'), () => this.onClickConfirmSow());
      }
    },

    /**
     * Gather sow informations and send atomic action
     */
    onClickConfirmSow() {
      // Compute selected crops
      let crops = [];
      this.forEachSowRadios((radio, zone) => {
        if (radio.checked && radio.value != 'neutral') {
          crops.push({
            id: zone.uid,
            crop: radio.value,
          });
        }
      });

      this.takeAtomicAction('actSow', [crops]);
    },

    tplSowSwitch(zone) {
      let id = zone.sid;
      return this.formatStringMeeples(`
      <div class="switch-wrapper" id="switch-holder-${id}" data-id="${id}">
        <input type="radio" name="switch-${id}" class="switch-grain" id="switch-grain-${id}" value="grain" />
        <label for="switch-grain-${id}"> <GRAIN> </label>

        <input type="radio" name="switch-${id}" class="switch-neutral" checked id="switch-neutral-${id}" value="neutral" />
        <label for="switch-neutral-${id}"></label>

        <input type="radio" name="switch-${id}" class="switch-vegetable" id="switch-vegetable-${id}" value="vegetable" />
        <label for="switch-vegetable-${id}"> <VEGETABLE> </label>

        <div class="switch-ball"></div>
      </div>
      `);
    },

    /**
     * Return container for seeds, creating it if needed
     */
    getSeedContainer(field) {
      if (
        field &&
        field.x !== undefined &&
        field.y !== undefined &&
        this.getCell(field, field.pId) &&
        !this.getCell(field, field.pId).querySelector('.meeple-field')
      ) {
        return this.getPastureSeedContainer(field);
      }

      if (!$('meeple-' + field.id)) {
        if (!$(field.id)) {
          console.error('Trying to sow on a non-existing field', field);
          return 'game_play_area';
        } else {
          // Card field
          return $(field.id).querySelector('.resource-holder');
        }
      }

      if (!$('seed-holder-' + field.id)) {
        this.place('tplSeedContainer', field, 'meeple-' + field.id);
      }

      return 'seed-holder-' + field.id;
    },

    getPastureSeedContainer(field) {
      const pId = field.pId ?? this.player_id;
      const locations = this.getPastureSeedLocations(field, pId);
      if (locations.length === 0) {
        console.error('Trying to sow on a pasture without locations', field);
        return 'game_play_area';
      }
      const holderId = `pasture-seed-holder-${pId}-${this.getPastureSeedLocationsKey(locations)}`;
      const anchor = this.getPastureSeedAnchor(field, locations);
      this.ensurePastureSeedContainer(holderId, locations, pId, anchor);
      return holderId;
    },

    getPastureSeedAnchor(field, locations) {
      if (
        field &&
        field.x !== undefined &&
        field.y !== undefined &&
        Array.isArray(locations) &&
        locations.some((loc) => loc.x == field.x && loc.y == field.y)
      ) {
        return { x: Number(field.x), y: Number(field.y) };
      }

      if (Array.isArray(locations) && locations.length > 0) {
        return locations[0];
      }

      return null;
    },

    getPastureSeedLocations(field, pId) {
      if (Array.isArray(field.locations) && field.locations.length > 0) {
        return field.locations;
      }

      if (field && field.x !== undefined && field.y !== undefined) {
        const pasture = this.findEligiblePastureAt(field, pId);
        if (pasture != null) {
          return pasture;
        }

        return [{ x: field.x, y: field.y }];
      }

      return [];
    },

    getPastureSeedLocationsKey(locations) {
      const coords = locations.map((loc) => `${loc.x}_${loc.y}`);
      coords.sort();
      return coords.join('-');
    },

    getEligiblePastureLocations(pId) {
      const player = this.gamedatas.players?.[pId];
      if (!player || !player.board) {
        return [];
      }

      const dropZones = player.board.dropZones ?? [];
      const fromDropZones = dropZones
        .filter((zone) => zone.type === 'pasture' && Array.isArray(zone.locations))
        .map((zone) => zone.locations)
        .filter((locations) => locations.length === 1 || locations.length === 2);

      if (fromDropZones.length > 0) {
        return fromDropZones;
      }

      const pastures = player.board.pastures ?? [];
      return pastures
        .filter((pasture) => Array.isArray(pasture.nodes))
        .map((pasture) => pasture.nodes)
        .filter((nodes) => nodes.length === 1 || nodes.length === 2);
    },

    findEligiblePastureAt(field, pId) {
      const key = `${field.x}_${field.y}`;

      for (const locations of this.getEligiblePastureLocations(pId)) {
        for (const loc of locations) {
          if (`${loc.x}_${loc.y}` === key) {
            return locations;
          }
        }
      }

      return null;
    },

    ensurePastureSeedContainer(holderId, locations, pId, anchor = null) {
      const board = $(`board-${pId}`);
      if (!board) {
        console.error('Trying to sow on a non-existing board', { holderId, pId, locations });
        return;
      }

      const cells = locations.map((loc) => this.getCell(loc, pId)).filter((cell) => cell != null);
      if (cells.length === 0) {
        console.error('Trying to sow on non-existing pasture cells', { holderId, pId, locations });
        return;
      }

      let holder = $(holderId);
      if (!holder) {
        holder = dojo.place(
          `<div class="seed-holder pasture-seed-holder pasture-seed-holder-slot" id="${holderId}"></div>`,
          board,
        );
      }

      let left = 0;
      let top = 0;
      const anchorCell =
        anchor && anchor.x !== undefined && anchor.y !== undefined ? this.getCell(anchor, pId) : null;

      if (anchorCell) {
        left = anchorCell.offsetLeft + anchorCell.offsetWidth / 2;
        top = anchorCell.offsetTop + anchorCell.offsetHeight / 2;
      } else {
        const minLeft = Math.min(...cells.map((cell) => cell.offsetLeft));
        const maxRight = Math.max(...cells.map((cell) => cell.offsetLeft + cell.offsetWidth));
        const minTop = Math.min(...cells.map((cell) => cell.offsetTop));
        const maxBottom = Math.max(...cells.map((cell) => cell.offsetTop + cell.offsetHeight));
        left = (minLeft + maxRight) / 2;
        top = (minTop + maxBottom) / 2;
      }

      holder.style.left = `${left}px`;
      holder.style.top = `${top}px`;
      holder.style.transform = 'translate(-50%, -50%)';
      if (anchor && anchor.x !== undefined && anchor.y !== undefined) {
        holder.setAttribute('data-anchor-x', `${anchor.x}`);
        holder.setAttribute('data-anchor-y', `${anchor.y}`);
      } else {
        holder.removeAttribute('data-anchor-x');
        holder.removeAttribute('data-anchor-y');
      }
    },

    tplSeedContainer(field) {
      return `
      <div class="seed-holder" id="seed-holder-${field.id}"></div>
      `;
    },

    /**
     * Sowing notification
     */
    notif_sow(n) {
      debug('Notif: sowing seeds', n);
      // Remove switches if needed
      this.clearSowRadios();
      // Undo/restart belong to the pre-sow state; next state re-adds them if still relevant
      dojo.destroy('btnUndoLastStep');
      dojo.destroy('btnRestartTurn');

      let mult = this.getAnimationSpeedMultiplier();
      let resources = [];
      n.args.sows.forEach((sow, i) => {
        if (sow.seed != null) {
          sow.seed.slideConfig = { delay: Math.round(200 * i * mult) };
          resources.push(sow.seed);
        }

        sow.crops.forEach((crop, j) => {
          crop.slideConfig = {
            delay: Math.round((200 * i + 100 * (j + 1)) * mult),
            from: 'page-title',
          };
          resources.push(crop);
        });
      });

      this.slideResources(resources, (meeple) => meeple.slideConfig);
    },

    /********************************
     ********* D75_WoodField *********
     ********************************/
    createWoodFieldRadios(zone) {
      zone.sid = zone.uid;
      this.place('tplSowWoodSwitch', zone, this.getSowZoneContainer(zone));
      document.getElementsByName('switch-' + zone.sid).forEach((radio) => {
        dojo.connect(radio, 'change', () => this.updateSowSwitches());
      });
    },

    tplSowWoodSwitch(zone) {
      let id = zone.sid;
      return this.formatStringMeeples(`
      <div class="switch-wrapper wood-switch" id="switch-holder-${id}" data-id="${id}">
        <input type="radio" name="switch-${id}" class="switch-neutral" checked id="switch-neutral-${id}" value="neutral" />
        <label for="switch-neutral-${id}"></label>

        <input type="radio" name="switch-${id}" class="switch-wood" id="switch-wood-${id}" value="wood" />
        <label for="switch-wood-${id}"> <WOOD> </label>

        <div class="switch-ball"></div>
      </div>
      `);
    },

    /********************************
     ********* E80_RockGarden *********
     ********************************/
    createStoneFieldRadios(zone) {
      zone.sid = zone.uid;
      this.place('tplSowStoneSwitch', zone, this.getSowZoneContainer(zone));
      document.getElementsByName('switch-' + zone.sid).forEach((radio) => {
        dojo.connect(radio, 'change', () => this.updateSowSwitches());
      });
    },

    tplSowStoneSwitch(zone) {
      let id = zone.sid;
      return this.formatStringMeeples(`
      <div class="switch-wrapper stone-switch" id="switch-holder-${id}" data-id="${id}">
        <input type="radio" name="switch-${id}" class="switch-neutral" checked id="switch-neutral-${id}" value="neutral" />
        <label for="switch-neutral-${id}"></label>

        <input type="radio" name="switch-${id}" class="switch-stone" id="switch-stone-${id}" value="stone" />
        <label for="switch-stone-${id}"> <STONE> </label>

        <div class="switch-ball"></div>
      </div>
      `);
    },
  });
});
