define(['dojo', 'dojo/_base/declare'], (dojo, declare) => {
  return declare('agricola.campaign', null, {
    setupCampaignPanel() {
      const c = this.gamedatas.campaign;
      let panel = $('campaign-panel');
      if (!c) {
        if (panel) dojo.destroy(panel);
        return;
      }
      if (!panel) {
        dojo.place("<div id='campaign-panel'></div>", 'game-flow-panel', 'before');
      }
      this.renderCampaignPanel();
    },

    // Goal track HTML: one cell per game (1..8), passed games green, current highlighted.
    // Shared by the side panel and the new-game modal.
    _campaignTrackHtml(c) {
      const goals = c.goals || [];
      const goal = c.goal;
      const idx = goals.indexOf(goal); // current goal's slot; -1 once past the table (extended play)
      const extended = idx === -1;

      let cells = '';
      for (let i = 0; i < goals.length; i++) {
        let cls = 'cp-cell';
        if (extended || i < idx) cls += ' cp-hit'; // games already cleared
        else if (i === idx) cls += ' cp-current'; // current game
        cells += `<span class='${cls}'><span class='cp-game'>${i + 1}</span><span class='cp-target'>${goals[i]}</span></span>`;
      }
      if (extended) {
        cells += `<span class='cp-cell cp-current'><span class='cp-game'>${goals.length + 1}+</span><span class='cp-target'>${goal}</span></span>`;
      }
      return cells;
    },

    renderCampaignPanel() {
      const c = this.gamedatas.campaign;
      const panel = $('campaign-panel');
      if (!c || !panel) return;

      let chips = '';
      (c.results || []).slice(-12).forEach((r) => {
        chips += `<span class='cp-chip ${r.hit ? 'cp-hit' : 'cp-miss'}' title='${_('Goal')} ${r.goal}'>${r.score}</span>`;
      });

      let html = `<div class='cp-head'>${_('Solo Campaign')}</div>`;
      html += `<div class='cp-track'>${this._campaignTrackHtml(c)}</div>`;
      if (chips != '') {
        html += `<div class='cp-history'><span class='cp-history-label'>${_('Last scores')}:</span>${chips}</div>`;
      }
      panel.innerHTML = html;
    },

    onEnteringStateCampaignChoosePermanent(args) {
      if (!this.isCurrentPlayerActive()) return;

      const cards = (args._private && args._private.cards) || [];
      const canChoose = args.success && cards.length > 0;

      // Title
      let title;
      if (args.justCompletedCampaign) {
        // The win that clears the final (8th) target — completing the campaign
        title = '<span class="campaign-cheer">' + _('Congratulations! You completed all 8 targets.') + '</span>';
        title += '<br>' + (canChoose
          ? _('Choose a played occupation to make permanent:')
          : _('End the campaign, or see how far you can get by adding 1 to the target score each game.'));
      } else if (args.success) {
        title = '<span class="campaign-cheer">' +
          _('Well done! You scored ${score} (target ${goal}).')
            .replace('${score}', args.score)
            .replace('${goal}', args.goal) +
          '</span>';
        if (canChoose) {
          title += '<br>' + _('Choose a played occupation to make permanent:');
        }
      } else {
        title = _('You scored ${score} points and missed the goal of ${goal} points.')
          .replace('${score}', args.score)
          .replace('${goal}', args.goal);
      }
      let titleEl = $('pagemaintitletext');
      if (titleEl) titleEl.innerHTML = title;

      if (canChoose) {
        cards.forEach((card) => {
          this.addPrimaryActionButton('btnPerm_' + card.id, _(card.name), () =>
            this.takeAction('actChoosePermanent', { cardId: card.id }, false)
          );
        });
        // Game 1 forces a choice; from game 2 on, the player may decline
        if (!args.firstGame) {
          this.addSecondaryActionButton('btnPermNone', _('None'), () =>
            this.takeAction('actProceed', {}, false)
          );
        }
      } else {
        let continueLabel = args.success
          ? _('Continue (new goal: ${goal})').replace('${goal}', args.nextGoal)
          : _('Try again (start with no food)');
        this.addPrimaryActionButton('btnCampaignProceed', continueLabel, () =>
          this.takeAction('actProceed', {}, false)
        );
      }

      // "End campaign" offered after a miss (give up), or once the 8-game arc is complete (optional stop)
      if (!args.success || args.completedArc) {
        let msg = args.completedArc
          ? _('Finish the campaign here? You have completed all 8 games.')
          : _('End the campaign now? The current game will be your final result.');
        this.addSecondaryActionButton('btnEndCampaign', _('End campaign'), () => {
          this.confirmationDialog(msg, () => this.takeAction('actEndCampaign', {}, false));
        });
      }
    },

    notif_campaignPermanentChosen(n) {
      debug('Notif: campaign permanent chosen', n);
    },

    notif_campaignNewGame(n) {
      debug('Notif: campaign new game', n);
      // The board was rebuilt server-side. Meeples / public cards / hand / scores are
      // refreshed by the refreshUI + refreshHand notifications that precede this one.
      // Here we reset what those don't cover: the action-card row, turn counter,
      // harvest icons and the campaign panel — no page reload needed.
      this.gamedatas.cards = n.args.cards;
      this.gamedatas.turn = n.args.turn;
      this.gamedatas.campaign = n.args.campaign;

      this.resetActionCardsForNewGame();
      this.rebuildHarvestIcons();
      dojo.attr('game_play_area', 'data-turn', n.args.turn);

      this._gameFlowPhase = null;
      this._gameFlowSubphase = null;
      this.renderGameFlow();
      this.renderCampaignPanel();
    },

    // Remove the now fully-revealed round cards and recreate empty (hidden) turn slots.
    // The new game's reveals repopulate them one per round, like a fresh game.
    resetActionCardsForNewGame() {
      for (let i = 1; i <= 14; i++) {
        let node = $('turn_' + i);
        if (node) dojo.destroy(node);
      }
      for (let i = 1; i <= 14; i++) {
        this.place('tplTurnContainer', i, 'central-board');
      }
      this.setupActionCardsHelpers();
      // Newly created future-goods holders have no data-n yet; mark them empty so they hide
      this.updateResourcesHolders(false, false);
    },

    rebuildHarvestIcons() {
      [4, 7, 9, 11, 13, 14].forEach((turn) => {
        let existing = $('harvest-' + turn);
        if (existing) dojo.destroy(existing);
        let slot = $('harvest-slot-' + turn);
        if (slot && turn >= (this.gamedatas.turn || 0)) {
          dojo.place(`<div id="harvest-${turn}" class="harvest-icon"></div>`, slot);
          this.addCustomTooltip('harvest-' + turn, _('Harvest will take place at the end of that turn'));
        }
      });
    },

    notif_campaignComplete(n) {
      debug('Notif: campaign complete', n);

      const results = n.args.results || [];
      const goals = n.args.goals || [];
      const hits = results.filter((r) => r.hit).length;
      const best = results.length ? Math.max(...results.map((r) => r.score)) : 0;
      const hitGoals = new Set(results.filter((r) => r.hit).map((r) => r.goal));

      // Goal track coloured by what was actually cleared
      let cells = '';
      for (let i = 0; i < goals.length; i++) {
        let cls = 'cp-cell' + (hitGoals.has(goals[i]) ? ' cp-hit' : '');
        cells += `<span class='${cls}'><span class='cp-game'>${i + 1}</span><span class='cp-target'>${goals[i]}</span></span>`;
      }

      let chips = '';
      results.slice(-16).forEach((r) => {
        chips += `<span class='cp-chip ${r.hit ? 'cp-hit' : 'cp-miss'}' title='${_('Goal')} ${r.goal}'>${r.score}</span>`;
      });

      const headline =
        hits >= goals.length
          ? _('You completed all ${total} games!').replace('${total}', goals.length)
          : _('You completed ${hits} of ${total} games.').replace('${hits}', hits).replace('${total}', goals.length);

      let contents = `
        <div class='campaign-intro'>
          <div class='ci-game'>${_('Campaign complete')}</div>
          <div class='cp-track'>${cells}</div>
          <div class='ci-line'>${headline}</div>
          <div class='ci-line'>${_('Best score')}: <strong>${best}</strong></div>`;
      if (chips != '') {
        contents += `<div class='cp-history'><span class='cp-history-label'>${_('Scores')}:</span>${chips}</div>`;
      }
      contents += `</div>`;

      this._campaignEndModal = new customgame.modal('campaignEnd', {
        class: 'agricola_popin',
        closeIcon: 'fa-times',
        closeAction: 'hide',
        closeWhenClickOnUnderlay: false,
        title: _('Solo Campaign'),
        contents: contents,
        autoShow: true,
        verticalAlign: 'center',
      });
    },

    // Summary modal shown before each new game begins (player clicks "Start game")
    onEnteringStateCampaignNewGameIntro(args) {
      if (!this.isCurrentPlayerActive()) return;

      let perms = (args.permanentList || []).map((p) => _(p.name)).join(', ');
      if (perms == '') {
        perms = _('None yet');
      }

      let lines = '';
      lines += `<div class='ci-line'>${_('Target score')}: <strong>${args.goal}</strong></div>`;
      lines += `<div class='ci-line'>${_('Permanent occupations')}: <strong>${perms}</strong></div>`;
      if (args.bonusFood > 0) {
        lines += `<div class='ci-line'>${_('Starting food bonus')}: <strong>${args.bonusFood}</strong></div>`;
      }
      if (args.retry) {
        lines += `<div class='ci-line ci-note'>${_('Replaying with the same cards and no bonus food.')}</div>`;
      }

      let contents = `
        <div class='campaign-intro'>
          <div class='ci-game'>${_('Game')} ${args.gameNumber}</div>
          <div class='cp-track'>${this._campaignTrackHtml(args.campaign || {})}</div>
          ${lines}
          <a id='campaign-intro-start' class='bgabutton bgabutton_blue'>${_('Start game')}</a>
        </div>
      `;

      this._campaignIntroModal = new customgame.modal('campaignIntro', {
        class: 'agricola_popin',
        closeIcon: 'fa-times',
        closeAction: 'hide',
        closeWhenClickOnUnderlay: false,
        title: _('Solo Campaign'),
        contents: contents,
        autoShow: true,
        verticalAlign: 'center',
      });

      let start = () => {
        if (this._campaignIntroModal) this._campaignIntroModal.hide();
        this.takeAction('actStartCampaignGame', {}, false);
      };
      if ($('campaign-intro-start')) dojo.connect($('campaign-intro-start'), 'onclick', start);
      this.addPrimaryActionButton('btnCampaignStart', _('Start game') + ' ' + args.gameNumber, start);
    },
  });
});
