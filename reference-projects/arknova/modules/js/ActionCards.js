define(['dojo', 'dojo/_base/declare'], (dojo, declare) => {
  return declare('arknova.actionCards', null, {
    updateActionCards() {
      this.getPlayers().forEach((player) => {
        if (player.actionCards)
          player.actionCards.forEach((card) => {
            // Real card
            let container = $(`action-card-slot-${player.id}-${card.strength}`);
            let o = $(`action-card-${card.id}`);
            if (!o) {
              o = this.place('tplActionCard', card, container);
              this.addCustomTooltip(o.id, this.tplActionCardTooltip(card), { midSize: false });
            } else {
              o.dataset.status = card.status;
              o.dataset.lvl = card.level;
              if (o.parentNode != container) {
                container.insertAdjacentElement('beforeend', o);
              }
            }

            // Summary
            let container2 = $(`overall_player_board_${player.id}`).querySelector(`.slot-${card.strength}`);
            let o2 = $(`summary-action-card-${card.id}`);
            if (!o2) {
              o2 = this.place('tplSummaryActionCard', card, container2);
              this.addCustomTooltip(o2.id, this.tplActionCardTooltip(card), { midSize: false });
            } else if (o2.parentNode != container2) {
              container2.insertAdjacentElement('beforeend', o2);
            }
          });
      });
      this.updateActionCardsSummaries();
    },

    notif_removeActionCards(n) {
      debug('Removing action cards', n);
      n.args.players_Id.forEach((id) => {
        for (i = 1; i <= 5; i++) {
          let container = $(`action-card-slot-${id}-${i}`);
          if (container) {
            container.children[1].remove();
          }
          document.querySelectorAll('.summary-action-card').forEach((node) => {
            node.remove();
          });
        }
      });
      return this.wait(500);
    },

    updateActionCardsSummaries() {
      this.getPlayers().forEach((player) => {
        for (let slot = 1; slot <= 5; slot++) {
          let card = $(`action-card-slot-${player.id}-${slot}`).querySelector('.action-card');
          if (!card) continue;

          let summaryCard = $(`summary-${card.id}`);

          if (card && summaryCard) {
            summaryCard.dataset.status = card.dataset.status;
            summaryCard.dataset.lvl = card.dataset.lvl;

            let container = $(`overall_player_board_${player.id}`).querySelector(`.slot-${slot}`);
            if (summaryCard.parentNode != container) {
              this.slide(summaryCard, container);
            }

            let meepleContainer = summaryCard.querySelector('.meeples-container');
            meepleContainer.innerHTML = '';
            let meeples = this.getClonedMeeplesOnActionCard(card);
            meeples.forEach((meeple) => {
              meepleContainer.appendChild(meeple);
            });
          }
        }
      });

      this.attachRegisteredTooltips();
    },

    getClonedMeeplesOnActionCard(elem, suffix = 'duplicated') {
      let container = elem.querySelector('.meeples-container');
      let meeples = [];
      container.childNodes.forEach((child) => {
        let o = child.cloneNode();
        let id = o.id;
        o.id += '_' + suffix;
        if (this.tooltips[id]) {
          this.registerCustomTooltip(this.tooltips[id].getContent(), o.id);
        }
        meeples.push(o);
      });

      return meeples;
    },

    makeActionCardsSelectable(cardIds, callback, pId = null) {
      for (let slot = 1; slot <= 5; slot++) {
        let card = $(`action-card-slot-${pId ? pId : this.player_id}-${slot}`).querySelector('.action-card');
        let cardId = parseInt(card.dataset.id);
        let summaryCard = $(`summary-${card.id}`);
        let selectable = cardIds.includes(cardId);

        // Add button in top bar
        this.addSecondaryActionButton(
          `btnChoose${cardId}`,
          this.formatIcon(`action-${card.dataset.type}`),
          selectable ? () => callback(cardId) : () => {}
        );
        $(`btnChoose${cardId}`).classList.add(`lvl${card.dataset.lvl}`);
        if (card.dataset.number != 0) {
          $(`btnChoose${cardId}`).dataset.number = card.dataset.number;
          $(`btnChoose${cardId}`).dataset.type = card.dataset.type;
        }
        $(`btnChoose${cardId}`).classList.toggle('disabled', !selectable);
        this.addCustomTooltip(`btnChoose${cardId}`, this.tooltips[card.id].getContent(), { midSize: false, forceRecreate: true });

        let meeples = this.getClonedMeeplesOnActionCard(card, 'duplicated_title');
        meeples.forEach((meeple) => {
          $(`btnChoose${cardId}`).appendChild(meeple);
        });

        if (selectable) {
          this.onClick(card, () => callback(cardId));
          this.onClick(summaryCard, () => callback(cardId));
        } else {
          card.classList.add('unselectable');
          summaryCard.classList.add('unselectable');
        }
      }

      this.attachRegisteredTooltips();
    },

    notif_chooseActionCard(n) {
      debug('Notif: choosing action card', n);
      $(`action-card-${n.args.actionCard.id}`).dataset.status = 1;
      this.updateActionCardsSummaries();
      return this.wait(1000);
    },

    notif_actionCardCleanup(n) {
      debug('Notif: action card cleanup', n);
      let summaryContainer = $(`overall_player_board_${n.args.player_id}`).querySelector('.action-cards-summary');
      let slideConfig = { phantom: false, changeParent: false, duration: this.getTiming(1400) };

      let promises = [];
      Object.keys(n.args.actionCards).forEach((cardId) => {
        let card = $(`action-card-${cardId}`);
        let summaryCard = $(`summary-action-card-${cardId}`);
        card.dataset.status = 0;
        summaryCard.dataset.status = 0;

        let strength = n.args.actionCards[cardId];
        let containerId = `action-card-slot-${n.args.player_id}-${strength}`;
        if (card.parentNode.id != containerId) {
          promises.push(this.slide(card, containerId, slideConfig));
          promises.push(this.slide(summaryCard, summaryContainer.querySelector(`.slot-${strength}`), slideConfig));
        }
      });
      return Promise.all(promises);
    },

    notif_upgradeCard(n) {
      debug('upgrading a card', n);
      $(`action-card-${n.args.actionCard.id}`).dataset.lvl = 2;
      this.updateActionCardsSummaries();
      return this.wait(1200);
    },

    tplSummaryActionCard(card) {
      let uid = 'summary-action-card-' + card.id;
      return `<div id="${uid}" data-id='${card.id}' data-lvl='${card.level}' data-type="${card.actionType}"
         data-status="${card.status}" data-number="${card.number}" class='summary-action-card'>
          ${this.formatIcon(`action-${card.actionType}`)}
          <div class='meeples-container'></div>
        </div>`;
    },

    tplActionCardTooltip(card) {
      let oCard = this.tplActionCard(card, true);

      return `<div class='action-card-tooltip'>
        ${oCard}
        <ul>
          ${card.tooltip.map((t) => `<li>${this.formatString(_(t))}</li>`).join('')}
        </ul>
      </div>`;
    },

    tplActionCard(card, tooltip = false) {
      let uid = (tooltip ? 'tooltip_action-card-' : 'action-card-') + card.id;

      let extensionIcon = '',
        cardNumber = '';
      if (card.number != 0) {
        extensionIcon = `<div class='ark-card-exp-icon'>
            <svg><use href="#mw-svg" /></svg>
          </div>`;
        cardNumber = `<div class='ark-card-number'>${card.number}</div>`;
      }

      // Introduced in MW to gain space on action cards
      let iconLvl1 = this.formatIcon('hand-cards');
      let iconLvl2 = this.formatIcon('hand-cards') + this.formatIcon('take-in-range-folder-cost');

      if (card.actionType == 'Cards') {
        iconLvl1 = this.formatIcon('take-in-deck');
        iconLvl2 = this.formatIcon('take-in-deck') + this.formatIcon('take-in-range');
      }
      if (card.actionType == 'Build') {
        iconLvl1 = '';
        iconLvl2 = '';
      }

      if (iconLvl1 != '') iconLvl1 = `<div class='ark-card-action-icon'>${iconLvl1}</div>`;
      if (iconLvl2 != '') iconLvl2 = `<div class='ark-card-action-icon'>${iconLvl2}</div>`;

      return `<div id="${uid}" data-id='${card.id}' data-lvl='${tooltip ? 1 : card.level}' data-type="${card.actionType}"
         data-number='${card.number}' data-status="${card.status}" class='ark-card action-card'>
        <div class='meeples-container'></div>
        <div class='action-card-perspective'>
          <div class='ark-card-wrapper recto'>
            <div class='ark-card-top'>
              <div class='ark-card-special'></div>
              <div class='ark-card-top-left'>
                <div class='arknova-icon icon-action-${card.actionType.toLowerCase()}'></div>
                I
              </div>
              ${iconLvl1}
            </div>
            <div class='ark-card-middle'>
              ${extensionIcon}
              ${cardNumber}
              <div class='ark-card-title-wrapper'>
                <div class='ark-card-title'>${_(card.name)}</div>
              </div>
            </div>
            <div class='ark-card-bottom'>
              ${card.descI.map((t) => this.tplActionCardDesc(card, t)).join('')}
            </div>
          </div>
          <div class='ark-card-wrapper verso'>
            <div class='ark-card-top'>
              <div class='ark-card-special'></div>
              <div class='ark-card-top-left'>
                <div class='arknova-icon icon-action-${card.actionType.toLowerCase()}'></div>
                II
              </div>
              ${iconLvl2}
            </div>
            <div class='ark-card-middle'>
              ${extensionIcon}
              ${cardNumber}
              <div class='ark-card-title-wrapper'>
                <div class='ark-card-title'>${_(card.name)}</div>
              </div>
            </div>
            <div class='ark-card-bottom'>
              ${card.descII.map((t) => this.tplActionCardDesc(card, t)).join('')}
            </div>
          </div>
        </div>
      </div>`;
    },

    tplActionCardDesc(card, t) {
      if (t == 'ANIMALS-I') {
        return this.formatString(`<table class='animals-I'>
            <tr>
              <td><STRENGTH:1></td>
              <td><STRENGTH:2></td>
              <td><STRENGTH:3></td>
              <td><STRENGTH:4></td>
              <td><STRENGTH:5></td>
            </tr>
            <tr>
              <td>0</td>
              <td>1</td>
              <td>1</td>
              <td>1</td>
              <td>2</td>
            </tr>
          </table>`);
      }
      if (t == 'ANIMALS-II') {
        return this.formatString(`<table class='animals-II'>
            <tr>
              <td><STRENGTH:1></td>
              <td><STRENGTH:2></td>
              <td><STRENGTH:3></td>
              <td><STRENGTH:4></td>
              <td><STRENGTH:5></td>
            </tr>
            <tr>
              <td>1</td>
              <td>1</td>
              <td>2</td>
              <td>2</td>
              <td><REPUTATION:1>2</td>
            </tr>
          </table>`);
      }
      if (t == 'CARDS-I') {
        return this.formatString(`<table class='cards-I'>
            <tr>
              <td></td>
              <td><STRENGTH:1></td>
              <td><STRENGTH:2></td>
              <td><STRENGTH:3></td>
              <td><STRENGTH:4></td>
              <td><STRENGTH:5></td>
            </tr>
            <tr>
              <th>${_('Draw')}</th>
              <td>1</td>
              <td>1</td>
              <td>2</td>
              <td>2</td>
              <td>3</td>
            </tr>
            <tr>
              <th>${_('Discard')}</th>
              <td>1</td>
              <td>-</td>
              <td>1</td>
              <td>-</td>
              <td>1</td>
            </tr>
            <tr>
              <th>${_('**OR** Snapping')}</th>
              <td>-</td>
              <td>-</td>
              <td>-</td>
              <td>-</td>
              <td><SNAPPING></td>
            </tr>
          </table>`);
      }
      if (t == 'CARDS-II') {
        return this.formatString(`<table class='cards-I'>
            <tr>
              <td></td>
              <td><STRENGTH:1></td>
              <td><STRENGTH:2></td>
              <td><STRENGTH:3></td>
              <td><STRENGTH:4></td>
              <td><STRENGTH:5></td>
            </tr>
            <tr>
              <th>${_('Draw')}</th>
              <td>1</td>
              <td>2</td>
              <td>2</td>
              <td>3</td>
              <td>4</td>
            </tr>
            <tr>
              <th>${_('Discard')}</th>
              <td>-</td>
              <td>1</td>
              <td>-</td>
              <td>1</td>
              <td>1</td>
            </tr>
            <tr>
              <th>${_('**OR** Snapping')}</th>
              <td>-</td>
              <td>-</td>
              <td><SNAPPING></td>
              <td><SNAPPING></td>
              <td><SNAPPING></td>
            </tr>
          </table>`);
      }

      if (t == 'SPONSORS') {
        return `<div class='separator'><hr>${_('OR')}<hr></div>`;
      }
      if (t == 'SEPARATOR') {
        return `<div class='separator-dashed'><hr></div>`;
      }

      // MW
      if (t == 'ANIMALS-I1') {
        return this.formatString(`<table class='animals-I'>
            <tr>
              <td><STRENGTH:1></td>
              <td><STRENGTH:2></td>
              <td><STRENGTH:3></td>
              <td><STRENGTH:4></td>
              <td><STRENGTH:5></td>
            </tr>
            <tr>
              <td>0</td>
              <td>1</td>
              <td>1</td>
              <td>1</td>
              <td class="wider">
                2 
                <div class="alternative">1<IGNORE-CONDITION></div>
              </td>
            </tr>
          </table>`);
      }
      if (t == 'ANIMALS-II1') {
        return this.formatString(`<table class='animals-II'>
            <tr>
              <td><STRENGTH:1></td>
              <td><STRENGTH:2></td>
              <td><STRENGTH:3></td>
              <td><STRENGTH:4></td>
              <td><STRENGTH:5></td>
            </tr>
            <tr>
              <td>1</td>
              <td>1</td>
              <td class="wider">
                  2
                  <div class="alternative">1<IGNORE-CONDITION></div>
              </td>
              <td class="wider">
                2
                <div class="alternative">1<IGNORE-CONDITION></div>
              </td>
              <td class="wider">
                <REPUTATION:1>2
                <div class="alternative"><REPUTATION:1>1<IGNORE-CONDITION></div>
              </td>
            </tr>
          </table>`);
      }
      if (t == 'CARDS-I1') {
        return this.formatString(`<table class='cards-I'>
            <tr>
              <td></td>
              <td><STRENGTH:1></td>
              <td><STRENGTH:2></td>
              <td><STRENGTH:3></td>
              <td><STRENGTH:4></td>
              <td><STRENGTH:5></td>
            </tr>
            <tr>
              <th class='bordered'>${_('Draw')}</th>
              <td>1</td>
              <td>1</td>
              <td>2</td>
              <td>2</td>
              <td>3</td>
            </tr>
            <tr>
              <th>${_('**OR** Snapping')}</th>
              <td>-</td>
              <td>-</td>
              <td>-</td>
              <td>-</td>
              <td><SNAPPING></td>
            </tr>
          </table>`);
      }
      if (t == 'CARDS-II1') {
        return this.formatString(`<table class='cards-I'>
            <tr>
              <td></td>
              <td><STRENGTH:1></td>
              <td><STRENGTH:2></td>
              <td><STRENGTH:3></td>
              <td><STRENGTH:4></td>
              <td><STRENGTH:5></td>
            </tr>
            <tr>
              <th class='bordered'>${_('Draw')}</th>
              <td>1</td>
              <td>2</td>
              <td>2</td>
              <td>3</td>
              <td>4</td>
            </tr>
            <tr>
              <th>${_('**OR** Snapping')}</th>
              <td>-</td>
              <td>-</td>
              <td><SNAPPING></td>
              <td><SNAPPING></td>
              <td><SNAPPING></td>
            </tr>
          </table>`);
      }

      if (t == 'CARDS-I3') {
        return this.formatString(`<table class='cards-I'>
            <tr>
              <td></td>
              <td><STRENGTH:1></td>
              <td><STRENGTH:2></td>
              <td><STRENGTH:3></td>
              <td><STRENGTH:4></td>
              <td><STRENGTH:5></td>
            </tr>
            <tr>
              <th>${_('Draw')}</th>
              <td>1</td>
              <td>1</td>
              <td>2</td>
              <td>2</td>
              <td>3</td>
            </tr>
            <tr>
              <th>${_('Discard')}</th>
              <td>1</td>
              <td>-</td>
              <td>1</td>
              <td>-</td>
              <td>1</td>
            </tr>
            <tr>
              <th>${_('**OR** Snapping')}</th>
              <td>-</td>
              <td>-</td>
              <td><SNAPPING></td>
              <td><SNAPPING></td>
              <td><SNAPPING></td>
            </tr>
          </table>`);
      }
      if (t == 'CARDS-II3') {
        return this.formatString(`<table class='cards-I'>
            <tr>
              <td></td>
              <td><STRENGTH:1></td>
              <td><STRENGTH:2></td>
              <td><STRENGTH:3></td>
              <td><STRENGTH:4></td>
              <td><STRENGTH:5></td>
            </tr>
            <tr>
              <th>${_('Draw')}</th>
              <td>1</td>
              <td>2</td>
              <td>2</td>
              <td>3</td>
              <td>4</td>
            </tr>
            <tr>
              <th>${_('Discard')}</th>
              <td>-</td>
              <td>1</td>
              <td>-</td>
              <td>1</td>
              <td>1</td>
            </tr>
            <tr>
              <th>${_('**OR** Snapping')}</th>
              <td>-</td>
              <td><SNAPPING></td>
              <td><SNAPPING></td>
              <td><SNAPPING></td>
              <td><SNAPPING><SNAPPING></td>
            </tr>
          </table>`);
      }

      return `<div>${this.translate(t)}</div>`;
    },

    //////////////////////////////////
    //// CHOOSE ACTION CARDS
    onEnteringStateChooseActionCard(args) {
      this.chooseActionCardVersion = this.settings.chooseActionCard;
      // NEW WAY
      if (this.settings.chooseActionCard == 1) {
        this.onEnteringStateChooseActionCardNewProcess(args);
      }
      // OLD WAY : ACTION CARD THEN STRENGTH
      else {
        let cardIds = Object.keys(args.cards).map((t) => parseInt(t));
        this.makeActionCardsSelectable(
          cardIds,
          (cardId) => {
            let type = $(`action-card-${cardId}`).dataset.type;
            this.clientState(
              'chooseActionCardSelectStrength',
              this.formatIcon(`action-${type}`) + ' ' + _('How do you want to use it?'),
              {
                cardId,
                strengths: args.cards[cardId],
              }
            );
          },
          args.pId ? args.pId : null
        );
      }

      // T1 MAP
      if (args.canUseT1) {
        this.addPrimaryActionButton('btnUseT1', this.formatString(_('Discard a card : <STRENGTH:+1>')), () => {
          this.clientState('mapT1Effect', _('Map T1 effect: choose a card to discard to gain <STRENGTH:+1>'), {
            cardIds: args._private.cardIds,
          });
        });
      }
    },

    // T1 MAP
    onEnteringStateMapT1Effect(args) {
      this.addCancelStateBtn();
      this.onSelectNCards(
        args.cardIds,
        {
          n: 1,
          class: 'selectedToDiscard',
          confirmText: _('Confirm Discard'),
          callback: (selectedElements, ignoredElements) => this.takeAtomicAction('actT1Effect', [selectedElements[0]]),
        },
        'hand'
      );
    },

    onEnteringStateChooseActionCardSelectStrength(args) {
      this.addCancelStateBtn();
      Object.keys(args.strengths).forEach((strength) => {
        if (strength == 0) return;
        let xtokens = args.strengths[strength];
        let desc =
          xtokens == 0
            ? this.formatString(`<STRENGTH:${strength}>`)
            : this.formatString(`<STRENGTH:${strength - xtokens}> + ${xtokens}<XTOKEN> = <STRENGTH:${strength}>`);

        this.addPrimaryActionButton(`btnUseCardStrength${strength}`, desc, () => {
          this.takeAtomicAction('actChooseActionCard', [args.cardId, strength]);
        });
      });

      // Gain XToken = strength 0
      if (args.strengths[0] !== undefined) {
        this.addPrimaryActionButton('btnUseCardStrength0', this.formatString(_('Gain 1<XTOKEN>')), () => {
          this.takeAtomicAction('actChooseActionCard', [args.cardId, 0]);
        });
      }
    },

    // NEW WAY : CLICK = GO
    onEnteringStateChooseActionCardNewProcess(args) {
      let pId = args.pId ? args.pId : null;
      let xtokens = 0;

      // Create the buttons
      let strengths = args.strengths;
      for (let slot = 1; slot <= 5; slot++) {
        let oSlot = $(`action-card-slot-${pId ? pId : this.player_id}-${slot}`);
        let card = oSlot.querySelector('.action-card');
        let cardId = parseInt(card.dataset.id);

        this.addSecondaryActionButton(
          `btnChoose${cardId}`,
          `${this.formatIcon(`action-${card.dataset.type}`)} ${
            args.cards[cardId] == undefined ? '' : this.formatIcon('strength', 0)
          }`,
          () => {
            if (!$(`btnChoose${cardId}`).classList.contains('disabled'))
              this.takeAtomicAction('actChooseActionCard', [cardId, strengths[cardId] + xtokens]);
          }
        );
        this.onClick(card, () => {
          if (!$(`btnChoose${cardId}`).classList.contains('disabled'))
            this.takeAtomicAction('actChooseActionCard', [cardId, strengths[cardId] + xtokens]);
        });
        if (args.cards[cardId] == undefined) $(`btnChoose${cardId}`).classList.add('disabled');
        $(`btnChoose${cardId}`).classList.add(`lvl${card.dataset.lvl}`);
        if (card.dataset.number != 0) {
          $(`btnChoose${cardId}`).dataset.number = card.dataset.number;
          $(`btnChoose${cardId}`).dataset.type = card.dataset.type;
        }
        this.addCustomTooltip(`btnChoose${cardId}`, this.tooltips[card.id].getContent(), { midSize: false, forceRecreate: true });

        let meeples = this.getClonedMeeplesOnActionCard(card, 'duplicated_title');
        meeples.forEach((meeple) => {
          $(`btnChoose${cardId}`).appendChild(meeple);
        });
      }
      this.attachRegisteredTooltips();

      $('customActions').insertAdjacentHTML(
        'beforeend',
        `<span id='xtoken-modifier-container'>
        <span id="xtoken-modifier-minus">-</span>
        <span id="xtoken-modifier-current">0</span>
        ${this.formatIcon('xtoken')}
        <span id="xtoken-modifier-plus">+</span>
      </span>`
      );

      let updateStrengths = () => {
        Object.entries(strengths).forEach(([cardId, strength]) => {
          if (!$(`btnChoose${cardId}`) || !$(`btnChoose${cardId}`).querySelector('.icon-strength span')) return;

          let s = strength + xtokens;
          $(`btnChoose${cardId}`).querySelector('.icon-strength span').innerHTML = s;
          $(`btnChoose${cardId}`).classList.toggle(
            'disabled',
            args.cards[cardId] == undefined || args.cards[cardId][s] == undefined
          );
        });
        $('xtoken-modifier-minus').classList.toggle('disabled', xtokens == 0);
        $('xtoken-modifier-plus').classList.toggle('disabled', xtokens == args.xtokens);
        $('xtoken-modifier-current').innerHTML = xtokens;
      };
      updateStrengths();
      this.onClick('xtoken-modifier-minus', () => {
        if (xtokens == 0) return;
        xtokens--;
        updateStrengths();
      });
      this.onClick('xtoken-modifier-plus', () => {
        if (xtokens == args.xtokens) return;
        xtokens++;
        updateStrengths();
      });

      if (args.canGainXToken) {
        let desc = `${this.formatIcon('clever')} : 1${this.formatIcon('xtoken')}`;
        this.addPrimaryActionButton('btnGainXToken', desc, () =>
          this.clientState(
            'chooseActionCardClever',
            this.formatString(_('Which card do you want to place in slot 1 to gain 1<XTOKEN>?')),
            args
          )
        );
      }
    },

    onEnteringStateChooseActionCardClever(args) {
      this.addCancelStateBtn();
      let cardIds = Object.keys(args.cards)
        .filter((cardId) => args.cards[cardId][0] != undefined)
        .map((t) => parseInt(t));
      this.makeActionCardsSelectable(
        cardIds,
        (cardId) => this.takeAtomicAction('actChooseActionCard', [cardId, 0]),
        args.pId ? args.pId : null
      );
    },

    onChangeChooseActionCardSetting(val) {
      if (
        this.gamedatas &&
        this.gamedatas.gamestate &&
        this.gamedatas.gamestate.id == 20 &&
        this.chooseActionCardVersion &&
        this.chooseActionCardVersion != val
      ) {
        this.restoreServerGameState();
      }
    },

    //////////////////////////////////
    //    ____              _
    //   / ___|__ _ _ __ __| |___
    //  | |   / _` | '__/ _` / __|
    //  | |__| (_| | | | (_| \__ \
    //   \____\__,_|_|  \__,_|___/
    //////////////////////////////////

    onEnteringStateCards(args) {
      // Draw from deck
      if (args.cardIds.length > 0 && args.n > 1) {
        this.addPrimaryActionButton('btnDrawOneFromDeck', _('Draw one card from deck'), () =>
          this.takeAtomicAction('actDrawCards', [1], true)
        );
      }

      if (args.n > 0) {
        this.addPrimaryActionButton('btnDrawAllFromDeck', this.fsr(_('Draw all ${n} cards from deck'), { n: args.n }), () =>
          this.takeAtomicAction('actDrawCards', [args.n], true)
        );
      }

      // Snap / Take card
      let allCards = args.snap.length > 0 ? args.snap : args.cardIds;
      this.prepareCardsForSelection(allCards, 'pool');
      allCards.forEach((cardId) => {
        this.onClick(`card-${cardId}`, () => {
          let card = { id: cardId };
          this.loadSaveCard(card);
          this.clientState('cardsTakeOption', _('Confirm taking ${card_name}'), {
            cardId,
            card_id: cardId,
            card_name: card.name,
            // Prevent snap if they can just draw it
            canSnap: (args.nDraw == 0 || args.nSnap > 1 || !args.cardIds.includes(cardId)) && args.snap.includes(cardId),
            canDraw: args.nDraw > 0 && args.cardIds.includes(cardId),
          });
        });
      });
    },

    onEnteringStateCardsTakeOption(args) {
      this.addCancelStateBtn();
      $(`card-${args.cardId}`).classList.add('selected');

      if (args.canSnap) {
        this.addPrimaryActionButton('btnConfirmSnap', _('Snap'), () => this.takeAtomicAction('actSnapCard', [args.cardId]));
      }
      if (args.canDraw) {
        this.addPrimaryActionButton('btnConfirmDraw', _('Draw'), () => this.takeAtomicAction('actTakeCard', [args.cardId]));
      }
    },

    onEnteringStateDiscard(args) {
      this.openHand();
      let cards = {};
      args._private.cardIds.forEach((cardId) => (cards[cardId] = $(`card-${cardId}`)));
      this.onSelectN({
        elements: cards,
        n: args.n,
        class: 'selectedToDiscard',
        confirmText: _('Confirm Discard'),
        callback: (selectedElements) => this.takeAtomicAction('actDiscard', [selectedElements]),
      });
    },

    //////////////////////////////////
    //  ____        _ _     _
    // | __ ) _   _(_) | __| |
    // |  _ \| | | | | |/ _` |
    // | |_) | |_| | | | (_| |
    // |____/ \__,_|_|_|\__,_|
    //////////////////////////////////

    onLeavingStateBuild() {
      if (!this.isSpectator && $(`zoo-map-${this.player_id}`)) {
        [...$(`zoo-map-${this.player_id}`).querySelectorAll('.zoo-map-cell')].forEach((elt) => {
          delete elt.style.removeProperty('cursor');
        });
      }
    },

    onEnteringStateBuild(args, action = 'build') {
      const actionName =
        action == 'reconstruction' ? 'actReconstructionPlaceBack' : action == 'increaseSize' ? 'actIncreaseSize' : 'actBuild';
      let selection = null,
        selectionBuildingId = null;
      let rotation = 0;
      let hoveredCell = null;
      let pos = null;
      let oMap = $(`zoo-map-${this.player_id}`).querySelector('.zoo-board');

      // Add a visual representation on hover
      oMap.insertAdjacentHTML(
        'beforeend',
        `<div id='building-controls' class='inactive hovering'>
        <div id='building-controls-circle'>
          <div id="building-rotate-clockwise"><svg><use href="#rotate-clockwise-svg" /></svg></div>
          <div id="building-rotate-cclockwise"><svg><use href="#rotate-cclockwise-svg" /></svg></div>
          <div id="building-confirm-btn" class="action-button bgabutton bgabutton_blue">✓</div>
        </div>
      </div>`
      );
      oMap.insertAdjacentHTML('beforeend', this.tplBuilding({ type: '', state: 0 }, 'building-hover'));
      $('building-hover')
        .querySelector('.building-crosshairs')
        .insertAdjacentHTML(
          'beforeend',
          `<div id="building-rotate-clockwise-on-tile"><svg><use href="#rotate-clockwise-svg" /></svg></div>
          <div id="building-rotate-cclockwise-on-tile"><svg><use href="#rotate-cclockwise-svg" /></svg></div>`
        );

      // Move selection to a given position
      let moveSelection = (x, y, cell = null) => {
        this.placeBuilding('building-hover', x, y);
        this.placeBuilding('building-controls', x, y);

        let pos = args.buildings[selection].find((p) => p.pos.x == x && p.pos.y == y);
        let valid = pos && pos.rotations.includes(((rotation % 6) + 6) % 6);
        $('building-hover').classList.toggle('invalid', !valid);
        $('building-hover').style.transform = `rotate(${rotation * 60}deg)`;
        $('building-hover').querySelector('.building-crosshairs').style.transform = `rotate(${-rotation * 60}deg)`;

        let bottomCircle = $('building-controls').offsetTop + $('building-controls-circle').offsetHeight / 2;
        $('building-controls-circle').classList.toggle('bottom', bottomCircle > $('building-controls').parentNode.offsetHeight);
        $('building-controls').classList.toggle('invalid', !valid);

        if (cell === null) {
          cell = oMap.querySelector(`[data-x='${x}'][data-y='${y}']`);
        }
        if (cell) {
          cell.style.cursor = valid ? 'pointer' : 'not-allowed';
        }

        // Update button status
        if ($('btnConfirmBuild')) {
          $('btnConfirmBuild').classList.toggle('disabled', !valid);
          $('building-confirm-btn').classList.toggle('disabled', !valid);
        }
      };
      let updateSelection = () => {
        if (hoveredCell) {
          moveSelection(hoveredCell.dataset.x, hoveredCell.dataset.y, hoveredCell);
        } else if (pos.x == 0 && pos.y == 0) {
          moveSelection(0, 0);
        }
      };

      // Add building selectors in pagetitle
      $('pagesubtitle').insertAdjacentHTML('beforeend', '<div id="building-selector"></div>');
      let callback = (buildingType, buildingId = null) => {
        // Existing placement => keep the same one
        if (selection !== null) {
          if (action == 'reconstruction') {
            $(`building-${selectionBuildingId}`).classList.remove('selected');
          } else {
            $(`building-selection-${selection}`).classList.remove('selected');
          }

          selectionBuildingId = buildingId;
          selection = buildingType;
          if (['kiosk', 'pavilion', 'size-1', 'arcade', 'victory'].includes(buildingType)) rotation = 0;
          updateSelection();
        }
        // Otherwise, set it at (0,0) (not a real cell)
        else {
          selection = buildingType;
          selectionBuildingId = buildingId;
          pos = { x: 0, y: 0 };
          rotation = 0;
          moveSelection(0, 0);
        }
        let type = buildingType;
        if (type == 'pavilion1') type = 'pavilion';
        if (type == 'kiosk2') type = 'kiosk';

        $('building-hover').dataset.type = type;
        $('building-controls').dataset.type = type;
        if (action == 'reconstruction') {
          $(`building-${selectionBuildingId}`).classList.add('selected');
        } else {
          $(`building-selection-${selection}`).classList.add('selected');
        }

        // Compute new size of circle control
        $('building-controls').classList.remove('inactive');
        let w = $('building-hover').offsetWidth;
        let h = $('building-hover').offsetHeight;
        let cross = $('building-hover').querySelector('.building-crosshairs');
        let offsetW = cross.offsetLeft + cross.offsetWidth / 2;
        let offsetH = cross.offsetTop + cross.offsetHeight / 2;
        let dx = Math.max(offsetW, w - offsetW);
        let dy = Math.max(offsetH, h - offsetH);
        let radius = Math.sqrt(dx * dx + dy * dy);
        $('building-controls-circle').style.width = 2 * radius + 'px';
        $('building-controls-circle').style.height = 2 * radius + 'px';

        this.addPrimaryActionButton('btnRotateCClockwise', '<i class="fa fa-undo"></i>', () => incRotation(-1));
        this.addPrimaryActionButton('btnRotateClockwise', '<i class="fa fa-repeat"></i>', () => incRotation(1));
      };

      const buildableBuildingTypes = Object.keys(args.buildings);

      // SPONSOR RECONSTRUCTION : REPLACE EXISTING BUILDINGS
      if (action == 'reconstruction') {
        let placableBuildings = [];
        Object.entries(args.allBuildings).forEach(([buildingId, building]) => {
          let buildingType = building.type;

          if (buildableBuildingTypes.includes(buildingType)) {
            $(`building-${buildingId}`).classList.remove('unplacable');
            this.onClick(`building-${buildingId}`, () => callback(buildingType, buildingId));
            placableBuildings.push(building);
          } else {
            $(`building-${buildingId}`).classList.add('unplacable');
          }
        });

        if (placableBuildings.length == 1) {
          let building = placableBuildings[0];
          callback(building.type, building.id);
        }
      }
      // STANDARD CASE : PLACE NEW BUILDING
      else {
        const buildingTypes = args.allBuildings;
        buildingTypes.forEach((buildingType) => {
          let type = buildingType;
          let number = null;
          if (type == 'pavilion1') {
            number = 1;
            type = 'pavilion';
          }
          if (type == 'kiosk2') {
            number = 2;
            type = 'kiosk';
          }

          $('building-selector').insertAdjacentHTML(
            'beforeend',
            this.tplBuilding({ type, state: 0 }, `building-selection-${buildingType}`)
          );
          if (number) {
            $(`building-selection-${buildingType}`).insertAdjacentHTML(
              'beforeend',
              `<div class='action-card-special-icon' data-type='Build' data-number='${number}'></div>`
            );
          }

          if (buildableBuildingTypes.includes(buildingType)) {
            this.onClick(`building-selection-${buildingType}`, () => callback(buildingType));
          } else {
            if (args.free || args.allAffordableBuildings.includes(buildingType)) {
              $(`building-selection-${buildingType}`).classList.add('unplacable');
            } else {
              $(`building-selection-${buildingType}`).classList.add('unaffordable');
              $(`building-selection-${buildingType}`).insertAdjacentHTML('beforeend', this.formatIcon('money', 'X'));
            }
          }
        });
        if (buildableBuildingTypes.length == 1) {
          callback(buildableBuildingTypes[0]);
        }
      }

      // Listen on hovering on map cells
      this.onHoverCell = (cell) => {
        cell.style.cursor = 'default';
        if (selection !== null && (pos == null || (pos.x == 0 && pos.y == 0))) {
          let x = parseInt(cell.dataset.x);
          let y = parseInt(cell.dataset.y);
          hoveredCell = cell;
          moveSelection(x, y, cell);
          $('building-hover').classList.add('hovering');
          $('building-controls').classList.add('hovering');
        }
      };

      this.onClickCell = (cell) => {
        cell.style.cursor = 'default';
        if (selection !== null) {
          let x = parseInt(cell.dataset.x);
          let y = parseInt(cell.dataset.y);
          pos = { x, y };
          hoveredCell = cell;
          $('building-hover').classList.remove('hovering');
          $('building-controls').classList.remove('hovering');

          // Add confirm button
          this.addPrimaryActionButton('btnConfirmBuild', _('Confirm'), () => {
            if (!$('btnConfirmBuild').classList.contains('disabled')) {
              this.takeAtomicAction(actionName, [selection, pos, ((rotation % 6) + 6) % 6, selectionBuildingId]);
            }
          });
          moveSelection(x, y, cell);
        }
      };

      // Click on arrow to rotate
      let incRotation = (c) => {
        rotation += c;
        updateSelection();
      };
      this.onClick('building-rotate-clockwise', () => incRotation(1));
      this.onClick('building-rotate-cclockwise', () => incRotation(-1));
      this.onClick('building-rotate-clockwise-on-tile', () => incRotation(1));
      this.onClick('building-rotate-cclockwise-on-tile', () => incRotation(-1));
      this.onClick('building-confirm-btn', () => {
        if (!$('building-confirm-btn').classList.contains('disabled')) {
          this.takeAtomicAction(actionName, [selection, pos, ((rotation % 6) + 6) % 6, selectionBuildingId]);
        }
      });
      this.attachRegisteredTooltips();
    },

    //////////////////////////////////
    //// BUILD : MOVE ANIMALS
    onEnteringStateMoveAnimals(args) {
      let cards = args.animals;
      Object.keys(cards).forEach((cardId) => {
        this.onClick(`card-${cardId}`, () =>
          this.clientState('moveAnimalsChooseEnclosure', _('Choose an enclosure to free for this animal'), {
            cards,
            cardId,
          })
        );
      });
    },

    onEnteringStateMoveAnimalsChooseEnclosure(args) {
      this.addCancelStateBtn();
      $(`card-${args.cardId}`).classList.add('selected');

      let infos = this.getCardInfos(args.cardId);
      let selectedBuildingId = null,
        selectedN = null;

      let enclosureEntries = Object.entries(args.cards[args.cardId]);
      enclosureEntries.forEach(([type, enclosuresObj]) => {
        // How many "cubes" are we trying to fit?
        let n = 1;
        if (type != 'regular') {
          n = infos.specialEnclosure.cubes;
        }

        // For each valid enclosure of this type
        let enclosures = Object.entries(enclosuresObj);
        enclosures.forEach(([buildingId, nEnclosure]) => {
          this.onClick(`building-${buildingId}`, () => {
            // Multiple special enclosures case => jump to another state
            if (enclosures.length > 1 && n > 1) {
              args.type = type;
              args.selection = {};
              args.selection[buildingId] = 1;
              args.buildings = args.cards[args.cardId];
              args.card_id = args.cardId;
              args.action = 'actMoveAnimals';
              this.clientState(
                'releaseChooseSpecialEnclosureAllocation',
                _('Choose how to remove the cubes among the valid special enclosures'),
                args
              );
              return;
            }

            // Otherwise, highlight enclosure
            if (selectedBuildingId != null) {
              $(`building-${selectedBuildingId}`).classList.remove('selected');
            }
            selectedBuildingId = buildingId;
            selectedN = n;
            $(`building-${selectedBuildingId}`).classList.add('selected');
            this.addPrimaryActionButton('btnConfirmEnclosure', _('Confirm'), () => {
              let selection = {};
              selection[selectedBuildingId] = selectedN;
              this.takeAtomicAction('actMoveAnimals', [args.cardId, type, selection]);
            });
          });
        });
      });
    },

    ////////////////////////////////////////////////
    //     _          _                 _
    //    / \   _ __ (_)_ __ ___   __ _| |___
    //   / _ \ | '_ \| | '_ ` _ \ / _` | / __|
    //  / ___ \| | | | | | | | | | (_| | \__ \
    // /_/   \_\_| |_|_|_| |_| |_|\__,_|_|___/
    ////////////////////////////////////////////////

    onEnteringStateAnimals(args) {
      let cards = args.cards || args._private.cardIds;
      this.onSelectNCards(Object.keys(cards), {
        class: 'selectedToKeep',
        confirmText: _('Confirm Play'),
        n: 1,
        autoConfirm: true,
        callback: (selectedElements) => {
          let cardId = selectedElements[0];
          this.clientState('animalsChooseEnclosure', _('Choose an enclosure for this animal'), {
            cards,
            cardId,
          });
        },
      });

      this.openHand();
    },

    onEnteringStateAnimalsChooseEnclosure(args) {
      this.addCancelStateBtn();
      $(`card-${args.cardId}`).classList.add('selected', 'selectedToKeep');
      this.onEnteringStateAnimals(args);

      let infos = this.getCardInfos(args.cardId);
      let selectedBuildingId = null,
        selectedN = null,
        selectedType = null;

      let enclosureEntries = Object.entries(args.cards[args.cardId]);
      enclosureEntries.forEach(([type, enclosuresObj]) => {
        // Handle Flock Animals
        if (type == 'FlockAnimal') {
          this.addPrimaryActionButton('btnUseFlock', _('Use Flocking ability'), () => {
            this.takeAtomicAction('actAnimals', [args.cardId, type, {}]);
          });
          return;
        }

        // How many "cubes" are we trying to fit?
        let n = 1;
        if (type != 'regular') {
          n = infos.specialEnclosure.cubes;
        }

        // For each valid enclosure of this type
        let enclosures = Object.entries(enclosuresObj);
        enclosures.forEach(([buildingId, nEnclosure]) => {
          this.onClick(`building-${buildingId}`, () => {
            // Multiple special enclosures case => jump to another state
            if (enclosures.length > 1 && n > 1) {
              args.type = type;
              args.selection = {};
              args.selection[buildingId] = 1;
              this.clientState(
                'animalsChooseSpecialEnclosureAllocation',
                _('Choose how to allocate the cubes among the valid special enclosures'),
                args
              );
              return;
            }

            // Otherwise, highlight enclosure
            if (selectedBuildingId != null) {
              $(`building-${selectedBuildingId}`).classList.remove('selected');
            }
            selectedType = type;
            selectedBuildingId = buildingId;
            selectedN = n;
            $(`building-${selectedBuildingId}`).classList.add('selected');
            this.addPrimaryActionButton('btnConfirmEnclosure', _('Confirm'), () => {
              let selection = {};
              selection[selectedBuildingId] = selectedN;
              this.takeAtomicAction('actAnimals', [args.cardId, selectedType, selection]);
            });
          });
        });
      });

      // Auto select if only one choice
      if (enclosureEntries.length == 1) {
        [type, enclosuresObj] = enclosureEntries[0];
        if (type == 'FlockAnimal') return;

        // How many "cubes" are we trying to fit?
        let n = 1;
        if (type != 'regular') {
          n = infos.specialEnclosure.cubes;
        }

        let enclosures = Object.entries(enclosuresObj);
        if (enclosures.length == 1) {
          let buildingId = enclosures[0][0];
          let selection = {};
          selection[buildingId] = n;

          this.clientState('animalsAutoConfirm', _('Confirm the animal and the enclosure'), {
            cardId: args.cardId,
            type,
            buildingId,
            selection,
          });
        }
      }
    },

    onEnteringStateAnimalsAutoConfirm(args) {
      this.addCancelStateBtn();
      this.onEnteringStateAnimals(this.last_server_state.args);
      $(`card-${args.cardId}`).classList.add('selected');
      $(`building-${args.buildingId}`).classList.add('selected');

      this.addPrimaryActionButton('btnConfirmEnclosure', _('Confirm'), () => {
        this.takeAtomicAction('actAnimals', [args.cardId, args.type, args.selection]);
      });
    },

    onEnteringStateAnimalsChooseSpecialEnclosureAllocation(args) {
      this.addCancelStateBtn();
      $(`card-${args.cardId}`).classList.add('selected', 'selectedToKeep');
      let infos = this.getCardInfos(args.cardId);
      let enclosures = args.cards[args.cardId][args.type];

      let filledUp = false;
      let updateSelection = () => {
        let totalN = 0;

        Object.keys(enclosures).forEach((buildingId) => {
          let n = args.selection[buildingId] || 0;
          let b = $(`building-${buildingId}`);
          b.classList.toggle('selected', n > 0);
          let m = +b.dataset.state;
          b.querySelectorAll('.icon-token').forEach((token, i) => {
            token.classList.toggle('selected', i >= m && i < m + n);
          });

          b.classList.toggle('unselectable', n >= enclosures[buildingId]);
          totalN += n;
        });

        $('btnReset').classList.toggle('disabled', totalN == 0);
        filledUp = totalN == infos.specialEnclosure.cubes;
        $('btnConfirm').classList.toggle('disabled', !filledUp);
        if (filledUp) {
          Object.keys(enclosures).forEach((buildingId) => $(`building-${buildingId}`).classList.add('unselectable'));
        }
      };
      this.addSecondaryActionButton('btnReset', _('Reset allocation'), () => {
        args.selection = {};
        Object.keys(enclosures).forEach((buildingId) => $(`building-${buildingId}`).classList.remove('unselectable'));
        updateSelection();
      });
      this.addPrimaryActionButton('btnConfirm', _('Confirm'), () => {
        this.takeAtomicAction('actAnimals', [args.cardId, args.type, args.selection]);
      });

      Object.entries(enclosures).forEach(([buildingId, maxN]) => {
        this.onClick(`building-${buildingId}`, () => {
          if (filledUp || $(`building-${buildingId}`).classList.contains('unselectable')) return;

          args.selection[buildingId] = (args.selection[buildingId] || 0) + 1;
          updateSelection();
        });
      });

      updateSelection();
    },

    //////////////////////////////////////////////////////////
    //     _                       _       _   _
    //    / \   ___ ___  ___   ___(_) __ _| |_(_) ___  _ __
    //   / _ \ / __/ __|/ _ \ / __| |/ _` | __| |/ _ \| '_ \
    //  / ___ \\__ \__ \ (_) | (__| | (_| | |_| | (_) | | | |
    // /_/   \_\___/___/\___/ \___|_|\__,_|\__|_|\___/|_| |_|
    //////////////////////////////////////////////////////////

    onEnteringStateAssociation(args) {
      const slots = args._private.slots;
      let strengths = Object.keys(slots);
      let extraWorkers = 0;
      if (strengths[0] == 0) {
        strengths.push(strengths.splice(0, 1)[0]);
      }

      strengths.forEach((strength) => {
        let slot = slots[strength];
        let callback = (strength, option) => this.takeAtomicAction('actAssociation', [strength, option, extraWorkers]);
        const onlyOneOption = this.makeAssociationOptionsSelectable(strength, slot, callback);

        // If at least two options => let the user click on button on top or on worker space to
        //  go in clientstate to select the option he wants to play
        if (!onlyOneOption) {
          const btnMsgs = {
            3: _('Take a partner zoo'),
            4: _('Take a university'),
            5: _('Play or support a conservation project'),
          };

          let callback = () =>
            this.clientState(
              'associationSelectOption',
              this.formatString(
                this.fsr(_('Select an option for placing a worker at strength <STRENGTH:${strength}>'), { strength })
              ),
              { strength, slot, extraWorkers }
            );
          if (strength == 5) {
            callback = () =>
              this.clientState('associationSelectProject', _('Select the conservation project you want to play/support'), {
                cards: slot.options,
                bonusesSpaces: args.bonusesSpaces,
                extraWorkers,
              });
          }
          this.onClick(`association_${strength}`, callback);
          this.addPrimaryActionButton(`btnAssociation${strength}`, btnMsgs[strength], callback);
        }
      });

      // ASSOC 2 - LVL2
      if (args.useExtraWorkers) {
        $('customActions').insertAdjacentHTML(
          'beforeend',
          `<span id='extra-workers-modifier-container'>
          <span id="extra-workers-modifier-minus">-</span>
          +
          <span id="extra-workers-modifier-current">0</span>
          ${this.formatIcon('worker')}
          <span id="extra-workers-modifier-plus">+</span>
        </span>`
        );

        let updateStrengths = () => {
          strengths.forEach((strength) => {
            let slot = slots[strength];
            let isDoable = slot.workers + extraWorkers <= args.useExtraWorkers;
            if (slot.extraWorkers) isDoable = isDoable && extraWorkers >= slot.extraWorkers;

            const onlyOneOption = this.toggleAssociationOptions(strength, slot, !isDoable);
            if (!onlyOneOption) {
              $(`association_${strength}`).classList.toggle('disabled', !isDoable);
              $(`btnAssociation${strength}`).classList.toggle('disabled', !isDoable);
            }
          });

          $('extra-workers-modifier-minus').classList.toggle('disabled', extraWorkers == 0);
          $('extra-workers-modifier-plus').classList.toggle('disabled', extraWorkers == args.useExtraWorkers);
          $('extra-workers-modifier-current').innerHTML = extraWorkers;
        };
        updateStrengths();
        this.onClick('extra-workers-modifier-minus', () => {
          if (extraWorkers == 0) return;
          extraWorkers--;
          updateStrengths();
        });
        this.onClick('extra-workers-modifier-plus', () => {
          if (extraWorkers == args.useExtraWorkers) return;
          extraWorkers++;
          updateStrengths();
        });
      }
    },

    onEnteringStateAssociationSelectOption(args) {
      this.addCancelStateBtn();
      let extraWorkers = args.extraWorkers;
      let callback = (strength, option) => this.takeAtomicAction('actAssociation', [strength, option, extraWorkers]);
      this.makeAssociationOptionsSelectable(args.strength, args.slot, callback, true);
    },

    makeAssociationOptionsSelectable(strength, slot, gCallback, createAllButtons = false) {
      const methods = {
        0: 'getAssociationOptionDonate',
        2: 'getAssociationOptionTakeInRange',
        3: 'getAssociationOptionZooUniv',
        4: 'getAssociationOptionZooUniv',
        5: 'getAssociationOptionConservationProjects',
        'take-in-range-or-deck': 'getAssociation4DrawCard',
        'add-worker': 'getAssociationOptionHireWorker',
      };

      const onlyOneOption = slot.options.length == 1;
      Object.keys(slot.options).forEach((key) => {
        let option = slot.options[key];
        let opt = this[methods[strength]](option, key, slot);
        let callback = () => gCallback(strength, option);

        // Make meeple selectable if any
        if (opt.meeple) {
          this.onClick(opt.meeple, callback);
        }

        // Create button
        if (opt.btn && (createAllButtons || onlyOneOption)) {
          this.addPrimaryActionButton(`btnAssociation${strength}-${key}`, this.formatString(opt.btn), callback);
        }

        // Make worker zone selectable with same callback if only one option
        if (onlyOneOption && strength > 0) {
          this.onClick(`association_${strength}`, callback);
        }
      });

      return onlyOneOption;
    },

    toggleAssociationOptions(strength, slot, disabled) {
      const methods = {
        0: 'getAssociationOptionDonate',
        2: 'getAssociationOptionTakeInRange',
        3: 'getAssociationOptionZooUniv',
        4: 'getAssociationOptionZooUniv',
        5: 'getAssociationOptionConservationProjects',
        'take-in-range-or-deck': 'getAssociation4DrawCard',
        'add-worker': 'getAssociationOptionHireWorker',
      };

      const onlyOneOption = slot.options.length == 1;
      Object.keys(slot.options).forEach((key) => {
        let option = slot.options[key];
        let opt = this[methods[strength]](option, key, slot);

        if (opt.meeple) {
          opt.meeple.classList.toggle('disabled', disabled);
        }
        if (opt.btn && $(`btnAssociation${strength}-${key}`)) {
          $(`btnAssociation${strength}-${key}`).classList.toggle('disabled', disabled);
        }
        if (onlyOneOption && strength > 0) {
          $(`association_${strength}`).classList.toggle('disabled', disabled);
        }
      });

      return onlyOneOption;
    },

    getAssociationOptionDonate(option) {
      return { btn: this.fsr(_('Donate <MONEY:${n}> and finish action'), { n: option[1] }) };
    },

    getAssociation4DrawCard(option) {
      return { btn: this.formatString(_('<TAKE-IN-RANGE-OR-DECK> and finish action')) };
    },

    getAssociationOptionTakeInRange(option) {
      return { btn: _('Take <REPUTATION:2>') };
    },

    getAssociationOptionHireWorker(option) {
      return { btn: _('Hire a worker') };
    },

    getAssociationOptionZooUniv(option, key, slot) {
      let meeple = $(`meeple-${option}`);
      if (meeple) {
        return {
          meeple,
          btn: this.formatIcon(meeple.dataset.type, null, false),
        };
      } else {
        return {
          btn: this.formatIcon(slot.meeples[option].type, null, false),
        };
      }
    },

    /////////////////////////////////////
    // CONSERVATION PROJECTS
    getAssociationOptionConservationProjects(option, cardId) {
      return [];
    },

    // Select the project card
    onEnteringStateAssociationSelectProject(args) {
      this.addCancelStateBtn();
      Object.keys(args.cards).forEach((cardId) => {
        let card = { id: cardId };
        this.loadSaveCard(card);
        let card_name = `<span class="ark-log-card-name">${_(card.name)}</span>`;

        let callback = () => {
          this.clientState('associationSelectProjectSlot', _('Which slot of ${card_name} ?'), {
            cardId,
            card_id: cardId,
            card_name: card.name,
            slots: args.cards[cardId],
            bonusesSpaces: args.bonusesSpaces,
            extraWorkers: args.extraWorkers,
          });
        };
        this.addPrimaryActionButton(`btnCard${cardId}`, card_name, callback);
        this.addCustomTooltip(`btnCard${cardId}`, this.tplZooCard(card, true), { forceRecreate: true });
        this.onClick(`card-${cardId}`, callback);
      });
    },

    // Select the slot on that card
    onEnteringStateAssociationSelectProjectSlot(args) {
      this.addCancelStateBtn();
      $(`card-${args.cardId}`).classList.add('selected');

      let card = { id: args.cardId };
      this.loadSaveCard(card);
      $('customActions').insertAdjacentHTML('afterbegin', this.tplProjectCard(card, false, true));

      args.slots.forEach((slot) => {
        let sId = slot;
        let callback = () => {
          this.clientState('associationSelectToken', _('Which token do you want to free from your board ?'), {
            cardId: args.cardId,
            sId,
            animalId: null,
            bonusesSpaces: args.bonusesSpaces,
            extraWorkers: args.extraWorkers,
          });
        };

        if (slot.animalIds) {
          sId = slot.id;
          callback = () => {
            this.clientState('associationSelectAnimalToRelease', _('Which animal do you want to release ?'), {
              cardId: args.cardId,
              animalIds: slot.animalIds,
              sId,
              bonusesSpaces: args.bonusesSpaces,
              extraWorkers: args.extraWorkers,
            });
          };
        }

        let names = {
          0: _('First slot'),
          1: _('Second slot'),
          2: _('Third slot'),
        };
        this.addPrimaryActionButton(`btnSlot${sId}`, names[sId], callback);
        this.onClick(`card-${args.cardId}_${sId}`, callback);
        this.onClick(`duplicated_card-${args.cardId}_${sId}`, callback);
      });
    },

    // If it's a "release animal" project, select the animal to release
    onEnteringStateAssociationSelectAnimalToRelease(args) {
      this.addCancelStateBtn();
      $(`card-${args.cardId}`).classList.add('selected');
      $(`card-${args.cardId}_${args.sId}`).classList.add('selected');

      args.animalIds.forEach((cardId) => {
        let card = { id: cardId };
        this.loadSaveCard(card);
        let card_name = `<span class="ark-log-card-name">${_(card.name)}</span>`;

        let callback = () => {
          this.clientState('associationSelectToken', _('Which token do you want to free from your board ?'), {
            cardId: args.cardId,
            sId: args.sId,
            animalId: cardId,
            bonusesSpaces: args.bonusesSpaces,
            extraWorkers: args.extraWorkers,
          });
        };
        this.addPrimaryActionButton(`btnCard${cardId}`, card_name, callback);
        this.addCustomTooltip(`btnCard${cardId}`, this.tplZooCard(card, true), { forceRecreate: true });
        this.onClick(`card-${cardId}`, callback);
      });
    },

    // Now select the token on association board you want to free
    onEnteringStateAssociationSelectToken(args) {
      this.addCancelStateBtn();
      $(`card-${args.cardId}`).classList.add('selected');
      $(`card-${args.cardId}_${args.sId}`).classList.add('selected');
      let map = MAPS_DATA[this.gamedatas.players[this.player_id].mapId];
      args.bonusesSpaces.forEach((i) => {
        let space = map.bonusSpaces[i];
        this.addSecondaryActionButton(`btnToken${i}`, this.formatBonus(space.bonus, space.type), () =>
          this.takeAtomicAction('actConservationProject', [args.cardId, args.sId, i, args.animalId, args.extraWorkers])
        );
        $(`btnToken${i}`).classList.add('singleBonus');
      });
    },

    ///////////////////////////////////
    // RELEASE
    onEnteringStateRelease(args) {
      $(`card-${args.card_id}`).classList.add('selected');

      let infos = this.getCardInfos(args.card_id);
      let selectedBuildingId = null,
        selectedN = null,
        selectedType = null;

      Object.entries(args.buildings).forEach(([type, enclosuresObj]) => {
        // How many "cubes" are we trying to fit?
        let n = 1;
        if (type != 'regular') {
          n = infos.specialEnclosure.cubes;
        }

        // For each valid enclosure of this type
        let enclosures = Object.entries(enclosuresObj);
        enclosures.forEach(([buildingId, nEnclosure]) => {
          this.onClick(`building-${buildingId}`, () => {
            // Multiple special enclosures case => jump to another state
            if (enclosures.length > 1 && n > 1) {
              args.type = type;
              args.selection = {};
              args.selection[buildingId] = 1;
              args.action = 'actRelease';
              this.clientState(
                'releaseChooseSpecialEnclosureAllocation',
                _('Choose how to remove the cubes among the valid special enclosures'),
                args
              );
              return;
            }

            // Otherwise, highlight enclosure
            if (selectedBuildingId != null) {
              $(`building-${selectedBuildingId}`).classList.remove('selected');
            }
            selectedBuildingId = buildingId;
            selectedN = n;
            selectedType = type;
            $(`building-${selectedBuildingId}`).classList.add('selected');
            this.addPrimaryActionButton('btnConfirmEnclosure', _('Confirm'), () => {
              let selection = {};
              selection[selectedBuildingId] = selectedN;
              this.takeAtomicAction('actRelease', [selectedType, selection]);
            });
          });
        });
      });
    },

    onEnteringStateReleaseChooseSpecialEnclosureAllocation(args) {
      this.addCancelStateBtn();
      $(`card-${args.card_id}`).classList.add('selected');
      let infos = this.getCardInfos(args.card_id);
      let enclosures = args.buildings[args.type];

      let filledUp = false;
      let updateSelection = () => {
        let totalN = 0;

        Object.keys(enclosures).forEach((buildingId) => {
          let n = args.selection[buildingId] || 0;
          let b = $(`building-${buildingId}`);
          b.classList.toggle('selected', n > 0);
          let m = +b.dataset.state;
          b.querySelectorAll('.icon-token').forEach((token, i) => {
            token.classList.toggle('selected', i >= m - n && i < m);
          });

          b.classList.toggle('unselectable', n >= enclosures[buildingId]);
          totalN += n;
        });

        $('btnReset').classList.toggle('disabled', totalN == 0);
        filledUp = totalN == infos.specialEnclosure.cubes;
        $('btnConfirm').classList.toggle('disabled', !filledUp);
        if (filledUp) {
          Object.keys(enclosures).forEach((buildingId) => $(`building-${buildingId}`).classList.add('unselectable'));
        }
      };
      this.addSecondaryActionButton('btnReset', _('Reset allocation'), () => {
        args.selection = {};
        Object.keys(enclosures).forEach((buildingId) => $(`building-${buildingId}`).classList.remove('unselectable'));
        updateSelection();
      });
      this.addPrimaryActionButton('btnConfirm', _('Confirm'), () => {
        let ajaxArgs = [args.type, args.selection];
        if (args.action == 'actMoveAnimals') ajaxArgs.unshift(args.cardId);
        this.takeAtomicAction(args.action, ajaxArgs);
      });

      Object.entries(enclosures).forEach(([buildingId, maxN]) => {
        this.onClick(`building-${buildingId}`, () => {
          if (filledUp || $(`building-${buildingId}`).classList.contains('unselectable')) return;

          args.selection[buildingId] = (args.selection[buildingId] || 0) + 1;
          updateSelection();
        });
      });

      updateSelection();
    },

    /////////////////////////////////////////////////
    //  ____
    // / ___| _ __   ___  _ __  ___  ___  _ __ ___
    // \___ \| '_ \ / _ \| '_ \/ __|/ _ \| '__/ __|
    //  ___) | |_) | (_) | | | \__ \ (_) | |  \__ \
    // |____/| .__/ \___/|_| |_|___/\___/|_|  |___/
    //       |_|
    /////////////////////////////////////////////////

    onEnteringStateSponsors(args) {
      let addBreakForMoneyBtn = () => {
        if (args.canBreakForMoney) {
          let strength = args.canBreakForMoney / args.lvl;
          this.addPrimaryActionButton(
            'btnPickMoney',
            this.formatString(this.fsr(_('Break <BREAK:${n}>, Gain <MONEY:${m}>'), { n: strength, m: args.canBreakForMoney })),
            () => this.takeAtomicAction('actBreakForMoney', [])
          );
        }
      };
      addBreakForMoneyBtn();

      this.onSelectNCards(args._private.cardIds, {
        class: 'selectedToKeep',
        confirmText: _('Confirm Play'),
        n: 1,
        callback: (selectedElements) => {
          this.takeAtomicAction('actSponsors', [selectedElements[0]]);
        },
        updateCallback: (selectedElements) => {
          if (selectedElements.length > 0 && $('btnPickMoney')) {
            $('btnPickMoney').remove();
          } else {
            addBreakForMoneyBtn();
          }
        },
      });
    },

    // MARINE WORLDS UNIQUE ACTION CARDS
    onEnteringStateSponsorsDiscardCardGetBonus(args) {
      this.onSelectNCards(args._private.cardIds, {
        n: 1,
        autoConfirm: true,
        class: 'selectedToDiscard',
        confirmText: _('Confirm Discard'),
        callback: (selectedElements, ignoredElements) => {
          if (args.descSuffix == '3-2') {
            this.clientState('sponsorsDiscardCardChooseBonus', _('Which bonus do you want to get?'), {
              cardId: selectedElements[0],
            });
          } else {
            this.takeAtomicAction('actSponsorsDiscardCardGetBonus', [selectedElements[0], null]);
          }
        },
      });
    },

    onEnteringStateSponsorsDiscardCardChooseBonus(args) {
      this.addCancelStateBtn();
      $(`card-${args.cardId}`).classList.add('selected');
      this.addPrimaryActionButton('btnMoney', this.formatIcon('money', 4), () => {
        this.takeAtomicAction('actSponsorsDiscardCardGetBonus', [args.cardId, 'money']);
      });

      this.addPrimaryActionButton('btnStrength', this.formatIcon('strength', '+2'), () => {
        this.takeAtomicAction('actSponsorsDiscardCardGetBonus', [args.cardId, 'strength']);
      });
    },
  });
});
