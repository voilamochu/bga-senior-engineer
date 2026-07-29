define(['dojo', 'dojo/_base/declare'], (dojo, declare) => {
  const HARVEST_ROUNDS = new Set([4, 7, 9, 11, 13, 14]);

  return declare('agricola.gameFlow', null, {
    setupGameFlow() {
      this._gameFlowPhase = this.gamedatas.gameFlowPhase || null;
      this._gameFlowSubphase = null;
      if (this._gameFlowPhase === 'harvest') {
        this._gameFlowSubphase =
          this.gamedatas.fieldPhase ? 'field' :
          this.gamedatas.breedPhase ? 'breeding' :
          'feeding';
      }
      this.renderGameFlow();
    },

    renderGameFlow() {
      const panel = $('game-flow-panel');
      if (!panel) return;

      const round = this.gamedatas.turn || 0;
      if (this._gameFlowPhase && round > 0 && round < 15) {
        panel.innerHTML = this._buildGameFlowGameHTML();
        panel.className = 'gf-game';
      } else {
        panel.innerHTML = '';
        panel.className = '';
      }

    },

    _buildGameFlowGameHTML() {
      const round = this.gamedatas.turn || 0;
      const phase = this._gameFlowPhase;
      const sub = this._gameFlowSubphase;

      const ph = (p, label) =>
        `<span class="gf-ph${phase === p ? ' on' : ''}">${label}</span>`;

      const isHarvestRound = HARVEST_ROUNDS.has(round);

      let html = `<span class="gf-round">${_('Round')} <strong>${round}</strong><span class="gf-of"> / 14</span></span>`;
      html += `<div class="gf-track">`;
      html += ph('preparation', _('Preparation'));
      html += `<span class="gf-arr">▸</span>`;
      html += ph('work', _('Work'));
      html += `<span class="gf-arr">▸</span>`;
      html += ph('returning-home', _('Returning Home'));
      if (isHarvestRound) {
        html += `<span class="gf-arr">▸</span>`;
        html += ph('harvest', _('Harvest'));
      }

      if (phase === 'harvest') {
        const sb = (s, label) =>
          `<span class="gf-sub${sub === s ? ' on' : ''}">${label}</span>`;
        html += `<span class="gf-sub-intro">↳</span>`;
        html += sb('field', _('Field'));
        html += `<span class="gf-arr">▸</span>`;
        html += sb('feeding', _('Feeding'));
        html += `<span class="gf-arr">▸</span>`;
        html += sb('breeding', _('Breeding'));
      }

      html += `</div>`;
      return html;
    },

    notif_startNewTurn(n) {
      this.gamedatas.turn = n.args.round;
      this._gameFlowPhase = 'preparation';
      this._gameFlowSubphase = null;
      this.renderGameFlow();
    },

    notif_startWork(n) {
      this._gameFlowPhase = 'work';
      this._gameFlowSubphase = null;
      this.renderGameFlow();
    },

    notif_startReturnHome(n) {
      this._gameFlowPhase = 'returning-home';
      this._gameFlowSubphase = null;
      this.renderGameFlow();
    },

    // notif_startHarvest is defined in agricola.js (inline props override mixin),
    // so harvest phase-setting lives there. No handler needed here.

    notif_startHarvestField(n) {
      this._gameFlowPhase = 'harvest';
      this._gameFlowSubphase = 'field';
      this.renderGameFlow();
    },

    notif_startHarvestFeed(n) {
      this._gameFlowPhase = 'harvest';
      this._gameFlowSubphase = 'feeding';
      this.renderGameFlow();
    },

    notif_startHarvestBreed(n) {
      this._gameFlowPhase = 'harvest';
      this._gameFlowSubphase = 'breeding';
      this.renderGameFlow();
    },
  });
});
