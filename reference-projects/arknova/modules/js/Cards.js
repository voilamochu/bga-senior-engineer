const isObject = (obj) => {
  return typeof obj === 'object' && obj !== null && !Array.isArray(obj);
};

define(['dojo', 'dojo/_base/declare', g_gamethemeurl + 'modules/js/cardsData.js'], (dojo, declare) => {
  function isVisible(elem) {
    return !!(elem.offsetWidth || elem.offsetHeight || elem.getClientRects().length);
  }

  let winners = {
    13: 'JDansp',
    14: 'JDansp',
    15: 'dwarvintime',
    16: 'Tsong Chen',
    17: 'Bsyragb',
    18: 'BDWSSBB',
    19: 'sorryimlikethis',
    20: 'heris89647',
    21: 'SakuraEcho',
    22: 'broken-winged angel',
    23: 'What How',
    24: 'Tsong Chen',
  };

  return declare('arknova.cards', null, {
    getCardInfos(cardId) {
      let card = { id: cardId };
      this.loadSaveCard(card);
      return card;
    },
    getCardName(cardId) {
      let card = this.getCardInfos(cardId);
      return this.fsr('${card_name}', { i18n: ['card_name'], card_name: _(card.name), card_id: card.id });
    },

    setupCards() {
      // This function is refreshUI compatible
      let cardIds = this.gamedatas.cards.map((card) => {
        if (!$(`card-${card.id}`)) {
          this.addZooCard(card);
        }

        let o = $(`card-${card.id}`);
        if (!o) return null;

        let container = this.getCardContainer(card);
        if (o.parentNode != $(container)) {
          console.log(container, card);
          dojo.place(o, container);
        }

        if (card.id == 'card-S227_WazaSpecialAssignment') {
          if (card.extraDatas && card.extraDatas.choice) {
            o.dataset.choice = card.extraDatas.choice;
          } else {
            delete o.dataset.choice;
          }
        }
        if (card.extraDatas && card.extraDatas.pouch) {
          o.dataset.pouch = card.extraDatas.pouch;
        } else {
          delete o.dataset.pouch;
        }

        return card.id;
      });
      document.querySelectorAll('.ark-card.zoo-card').forEach((oCard) => {
        if (
          !cardIds.includes(oCard.getAttribute('data-id')) &&
          !oCard.parentNode.classList.contains('player-board-hand') &&
          !oCard.parentNode.classList.contains('player-board-scoring-hand') &&
          !oCard.parentNode.classList.contains('player-board-stored-hand')
        ) {
          this.destroy(oCard);
        }
      });
    },

    setupPoolCardsContainer() {
      let names = [
        '',
        _('San Diego, USA'),
        _('Frankfurt, GER'),
        _('Johannesburg, SA'),
        _('Sao Paulo, BR'),
        _('Okinawa, JP'),
        _('Sydney, AUS'),
      ];

      [1, 2, 3, 4, 5, 6].forEach((i) => {
        $('cards-pool').insertAdjacentHTML(
          'beforeend',
          `<div class='card-pool-folder'>
            <div class='folder-name'>${names[i]}</div>
            <div class='folder-number'>${i}</div>
            <div class='folder-cost'>${this.formatIcon('money', i)}</div>
            <div class='pool-slot-background'>
              <div id='pool-${i}' class='pool-slot'></div>
            </div>
        </div>`
        );
      });

      this.addTooltipToClass(
        'folder-cost',
        _(
          'Folder cost: you will need to pay that amount of money if you want to play directly this card from display using an upgraded action card (Animals II, Sponsors II or Association II)'
        ),
        ''
      );

      $('reputation-track').insertAdjacentHTML('beforeend', '<div class="badge-icon" data-type="CardsII"></div>');
      let reputationSize = [6, 3, 3];
      let reputationBonuses = {
        5: { 'upgrade-card': null },
        8: { 'add-worker': null },
        10: { 'take-in-range-or-deck': 1 },
        11: { conservation: 1 },
        12: { xtoken: 1 },
        13: { 'take-in-range-or-deck': 1 },
        14: { conservation: 1 },
        15: { xtoken: 1 },
      };
      for (let i = 1; i <= 15; i++) {
        let size = reputationSize[i - 1] ? reputationSize[i - 1] : 2;
        let bonus = reputationBonuses[i] ? this.formatBonus(reputationBonuses[i]) : '';

        $('reputation-track').insertAdjacentHTML(
          'beforeend',
          `<div class='reputation-slot ${i > 9 ? 'upgradeNeeded' : ''}' id="reputation-${i}" style="grid-column-end:span ${size}">
            ${bonus}
            <div id="reputation-${i}-holder" class='reputation-holder'></div>
          </div>`
        );
      }

      $('reputation-track').insertAdjacentHTML('beforeend', `<div id="max-reputation-bonus-holder"></div>`);
    },

    setupProjectsContainer() {
      let nPlayers = Object.keys(this.gamedatas.players).length;
      // Personnal projects
      for (let i = 0; i < Math.max(2, nPlayers); i++) {
        $('projects-holder').insertAdjacentHTML('beforeend', `<div class='project-holder' id="project-${i}"></div>`);
      }
      $('projects-holder').insertAdjacentHTML('beforeend', this.formatIcon('conservation-project'));

      // Base projects
      for (let i = 0; i < (nPlayers == 4 ? 4 : 3); i++) {
        $('base-projects-holder').insertAdjacentHTML('beforeend', `<div class='project-holder' id="base-project-${i}"></div>`);
      }
      $('base-projects-holder').insertAdjacentHTML('beforeend', this.formatIcon('conservation-project-base'));
    },

    /**
     * Smarter buffering of card details (to not resend them over notification again)
     */
    loadSaveCard(card) {
      let cardInfos = CARDS_DATA[card.id];
      for (let key in cardInfos) {
        card[key] = cardInfos[key];
      }
    },

    addZooCard(card, container = null) {
      if (card.fake) {
        this.place('tplFakeZooCard', card, container);
        return;
      }

      this.loadSaveCard(card);
      if (container === null) {
        container = this.getCardContainer(card);
      }
      let o = this.place('tplZooCard', card, container);
      if (o !== undefined) {
        this.addCustomTooltip(
          o.id,
          () => {
            let status = this.getCardStatus(card.id);
            return `<div class='zoo-card-tooltip'>
              ${status}
              ${this.tplZooCard(card, true)}
            </div>`;
          },
          { midSize: false }
        );
      }
    },

    countPartnerZoo(pId, continent) {
      if (!$(`zoo-map-${pId}`)) return 0;
      return $(`zoo-map-${pId}`).querySelectorAll(`.zoo-map-partner-zoos .arknova-meeple.icon-partner-${continent}`).length;
    },

    hasPlayedCard(pId, cardId) {
      return (
        $(`player-board-cards-${pId}`).querySelector(`.player-board-inPlay-animals #card-${cardId}`) != null ||
        $(`player-board-cards-${pId}`).querySelector(`.player-board-inPlay-sponsors #card-${cardId}`) != null
      );
    },

    getCardCosts(cardId, pId) {
      let cardInfos = this.getCardInfos(cardId);
      let cost = cardInfos.cost;

      // Only animals have reduction
      if (cardInfos.type == 'animal') {
        cardInfos.continents.forEach((continent) => {
          cost -= 3 * this.countPartnerZoo(pId, continent);
        });

        if (cardInfos.enclosureSize <= 2 && this.hasPlayedCard(pId, 'S229_ExpertInSmallAnimals')) {
          cost -= 3;
        }
        if (cardInfos.enclosureSize >= 4 && this.hasPlayedCard(pId, 'S230_ExpertInLargeAnimals')) {
          cost -= 4;
        }
      }

      return { originalCost: cardInfos.cost, reducedCost: Math.max(cost, 0) };
    },

    updateCardCosts() {
      if (this.isSpectator) return;

      const cards = [
        ...$('cards-pool').querySelectorAll('.ark-card'),
        ...$(`hand-${this.player_id}`).querySelectorAll('.ark-card'),
      ];
      cards.forEach((card) => {
        const cardId = card.dataset.id;

        let costHolder = card.querySelector('.animal-card-cost');
        if (!costHolder) return;
        let oReducedCost = costHolder.querySelector('.reduced-cost .icon-money');

        let costs = this.getCardCosts(cardId, this.player_id);
        let hasReducedCost = costs.originalCost != costs.reducedCost;
        costHolder.classList.toggle('reduced', hasReducedCost);
        if (+oReducedCost.innerHTML == costs.reducedCost) return;
        if (hasReducedCost) oReducedCost.innerHTML = costs.reducedCost;

        let oCard = this.getCardInfos(cardId);
        if (hasReducedCost) oCard.reducedCost = costs.reducedCost;
        this.tooltips[card.id].getContent = () => {
          let status = this.getCardStatus(cardId);
          return `<div class='zoo-card-tooltip'>
            ${status}
            ${this.tplAnimalCard(oCard, true)}
          </div>`;
        };
      });
    },

    getCardContainer(card) {
      let t = card.location.split('_');
      if (card.location == 'hand') {
        return $(`hand-${card.pId}`);
      } else if (card.location == 'scoringHand') {
        return $(`scoring-hand-${card.pId}`);
      } else if (card.location == 'stored') {
        return $(`stored-hand-${card.pId}`);
      } else if (card.location == 'inPlay') {
        return card.id[0] == 'S' ? $(`inPlay-sponsors-${card.pId}`) : $(`inPlay-animals-${card.pId}`);
      } else if (card.location == 'rescueStation') {
        return $(`rescueStation-${card.pId}-${card.state}`);
      } else if (t[0] == 'base') {
        return $(`base-project-${t[1]}`);
      } else if (t[0] == 'projects') {
        return $(`project-${t[1]}`);
      } else if ($(card.location)) {
        return $(card.location);
      }

      return $('test-cards');
    },

    tplZooCard(card, tooltip = false) {
      if (card.type == 'animal') {
        return this.tplAnimalCard(card, tooltip);
      } else if (card.type == 'project' || card.type == 'baseProject') {
        return this.tplProjectCard(card, tooltip);
      } else if (card.type == 'scoring') {
        return this.tplScoringCard(card, tooltip);
      } else if (card.type == 'sponsor') {
        return this.tplSponsorCard(card, tooltip);
      }

      console.error('No tpl yet');
      return '';
    },

    tplFakeZooCard(card) {
      let uid = 'card-' + card.id;
      return `<div id="${uid}" class='ark-card zoo-card fake-card'>
      <div class='ark-card-wrapper'></div>
    </div>`;
    },

    /**
     * Prepare cards for selection : get cards corresponding to ids,
     *   and make other in the same "location" unselectable
     */
    prepareCardsForSelection(cardIds, location = 'hand') {
      let container = null;
      if (location == 'hand') {
        this.openHand();
        container = $(`hand-${this.player_id}`);
      } else if (location == 'scoringHand') {
        this.openScoringHand();
        container = $(`scoring-hand-${this.player_id}`);
      } else if (location == 'storedHand') {
        this.openStoredHand();
        container = $(`stored-hand-${this.player_id}`);
      } else if (location == 'pool') {
        container = $('cards-pool');
      } else if (location == 'choice') {
        this._cardsChoiceModal = new customgame.modal('chooseCards', {
          class: 'arknova_popin',
          autoShow: true,
          closeIcon: 'fa-times',
          closeAction: 'hide',
          verticalAlign: 'flex-start',
          contentsTpl: `<div id='choose-cards'></div><div id='choose-cards-footer'></div>`,
          scale: 0.9,
          breakpoint: 800,
        });
        this.addPrimaryActionButton('btnShowCards', _('Show cards'), () => this._cardsChoiceModal.show());
        container = $('choose-cards');
      } else if (location == 'sponsors') {
        container = $(`inPlay-sponsors-${this.player_id}`);
      }

      let elements = {};
      cardIds.forEach((cardId) => {
        let oCard = $(`card-${cardId}`);
        if (!oCard) {
          let card = { id: cardId };
          this.loadSaveCard(card);
          this.addZooCard(card, container);
          oCard = $(`card-${cardId}`);
        }
        elements[cardId] = oCard;
        oCard.classList.remove('unselectable');
        oCard.classList.add('selectable');
      });

      let containers = [$(`hand-${this.player_id}`), $(`scoring-hand-${this.player_id}`), $('cards-pool'), $('choose-cards')];
      if (!containers.includes(container)) {
        containers.push(container);
      }
      containers.forEach((container) => {
        if (container) {
          [...container.querySelectorAll('.ark-card:not(.unselectable):not(.selectable)')].forEach((elt) =>
            elt.classList.add('unselectable')
          );
        }
      });

      return elements;
    },

    closeChooseCardsModal() {
      if (this._cardsChoiceModal) {
        this._cardsChoiceModal.destroy();
        delete this._cardsChoiceModal;
      }
    },

    /**
     * onSelectNCards : syntaxic sugar for calling prepareCardsForSelection and then onSelectN
     *  + default behavior for keep/discard choice when selecting n/2 cards over a pool of n cards
     */
    onSelectNCards(cardIds, config, location = 'hand') {
      let elements = this.prepareCardsForSelection(cardIds, location);
      config.elements = elements;
      let callback = config.confirmMsg
        ? (selectedElements) => {
            this.askConfirmation(config.confirmMsg(selectedElements), () => config.callback(selectedElements));
          }
        : config.callback;

      if (location == 'choice') {
        config.btnContainer = 'choose-cards-footer';
      }

      this.onSelectN(config);
    },

    /**
     * Private notification for the player drawing the card :
     *  create the cards and slide them in hand
     */
    async notif_pDrawCards(n) {
      debug('Notif: private drawing cards', n);
      this.closeChooseCardsModal();
      let counter = n.args.scoringCard ? 'scoringHandCount' : 'handCount';

      if (this.isFastMode()) {
        n.args.cards.forEach((card) => {
          this.addZooCard(card);
        });
        this.updateCardCosts();
        this._playerCounters[this.player_id][counter].incValue(n.args.cards.length);
        if (n.args.pilfering) this._playerCounters[n.args.pilfering][counter].incValue(-n.args.cards.length);
        else this._deckCounter.incValue(-n.args.cards.length);
        return;
      }

      const slidingDuration = this.getTiming(1000);
      await Promise.all(
        n.args.cards.map((card, i) => {
          return this.wait(100 * i).then(() => {
            this.addZooCard(card);

            let to = null;
            let container = this.getCardContainer(card);
            if (!isVisible(container)) to = $('floating-hand-button');
            let source = n.args.pilfering ? $(`counter-${n.args.pilfering}-${counter}`) : this.getVisibleTitleContainer();

            return this.slide(`card-${card.id}`, container, {
              from: source,
              duration: slidingDuration,
              to,
            });
          });
        })
      );

      this._playerCounters[this.player_id][counter].incValue(n.args.cards.length);
      if (n.args.pilfering) this._playerCounters[n.args.pilfering][counter].incValue(-n.args.cards.length);
      else this._deckCounter.incValue(-n.args.cards.length);

      this.updateCardCosts();
    },

    /**
     * Public notification when drawing cards:
     *  ignore if current player is the one drawing card
     *  slide fakes cards from titlebar to player panel and increase hand count
     */
    async notif_drawCards(n) {
      debug('Notif: public drawing cards', n);
      this.closeChooseCardsModal();
      let counter = n.args.scoringCard ? 'scoringHandCount' : 'handCount';

      let nCards = n.args.n;
      if (this.isFastMode()) {
        this._playerCounters[n.args.player_id][counter].incValue(nCards);
        this._deckCounter.incValue(-nCards);
        return;
      }

      const slidingDuration = this.getTiming(1000);
      await Promise.all(
        Array.from(Array(nCards), (x, i) => i).map((i) => {
          return this.wait(100 * i).then(() => {
            this.addZooCard({ id: i, fake: true }, this.getVisibleTitleContainer());
            return this.slide(`card-${i}`, `counter-${n.args.player_id}-${counter}`, {
              duration: slidingDuration,
              destroy: true,
              phantom: false,
            });
          });
        })
      );

      this._playerCounters[n.args.player_id][counter].incValue(nCards);
      this._deckCounter.incValue(-nCards);
    },

    /**
     * Public notification when someone snap a card
     *  slide it to hand if current player
     *  slide it to player panel otherwise
     *  inc hand count
     */
    async notif_snapCard(n) {
      debug('Snapping a card', n);
      let card = n.args.cards[0];
      const slidingDuration = this.getTiming(1000);

      // Current player => add to hand
      if (this.player_id == n.args.player_id) {
        await this.slide(`card-${card.id}`, this.getCardContainer(card), {
          duration: slidingDuration,
        });
      }
      // Otherwise, slide to counter
      else {
        await this.slide(`card-${card.id}`, `counter-${n.args.player_id}-handCount`, {
          duration: slidingDuration,
          destroy: true,
          phantom: false,
        });
      }

      // Inc counter
      this._playerCounters[n.args.player_id]['handCount'].incValue(1);
      // Update card costs
      this.updateCardCosts();
    },

    async notif_sponsorMagnet(n) {
      debug('Sponsor magnet', n);
      let cards = n.args.cards;
      const slidingDuration = this.getTiming(1000);

      // Current player => add to hand
      if (this.player_id == n.args.player_id) {
        await Promise.all(
          cards.map((card, i) =>
            this.slide(`card-${card.id}`, this.getCardContainer(card), {
              duration: slidingDuration,
            })
          )
        );
      }
      // Otherwise, slide to counter
      else {
        await Promise.all(
          cards.map((card, i) =>
            this.slide(`card-${card.id}`, `counter-${n.args.player_id}-handCount`, {
              duration: slidingDuration,
              destroy: true,
              phantom: false,
            })
          )
        );
      }

      // Update counter
      this._playerCounters[n.args.player_id]['handCount'].incValue(cards.length);
      this.updateCardCosts();
    },

    /**
     * Private notification for the player discarding the card :
     *  slide them and destroy them
     */
    async notif_pDiscardCards(n) {
      debug('Notif: private discarding cards', n);
      let counter = n.args.scoringCard ? 'scoringHandCount' : 'handCount';

      let targetPouch = null;
      if (n.args.pouch) {
        if (n.args.pouch == 'mapPouched') {
          targetPouch = $(`zoo-map-${this.player_id}`).querySelector('.map-infos');
        } else {
          targetPouch = $(`card-${n.args.pouch}`);
        }
      }

      if (this.isFastMode()) {
        n.args.cards.forEach((card) => {
          this.destroy($(`card-${card.id}`));
        });
      } else {
        await Promise.all(
          n.args.cards.map((card, i) => {
            let target =
              targetPouch != null
                ? targetPouch
                : n.args.pilfering
                  ? $(`counter-${n.args.pilfering}-${counter}`)
                  : this.getVisibleTitleContainer();
            return this.slide(`card-${card.id}`, target, {
              delay: this.getTiming(100 * i),
              duration: this.getTiming(1000),
              destroy: true,
              phantom: false,
            });
          })
        );
      }

      this._playerCounters[this.player_id][counter].incValue(-n.args.cards.length);
      if (n.args.pilfering) this._playerCounters[n.args.pilfering][counter].incValue(n.args.cards.length);
      else this._discardCounter.incValue(n.args.cards.length);
      if (n.args.pouch) {
        targetPouch.dataset.pouch = parseInt(targetPouch.dataset.pouch || 0) + n.args.cards.length;
      }

      if (n.args.bonuses) {
        await this.notif_getBonuses(n);
      }
    },

    /**
     * Public notification when discarding cards:
     *  ignore if current player is the one discarding card
     *  slide fakes cards from player panel to titlebar and decrease hand count
     */
    async notif_discardCards(n) {
      debug('Notif: public discarding cards', n);
      if (n.args.player_id == this.player_id) {
        return;
      }

      let counter = n.args.scoringCard ? 'scoringHandCount' : 'handCount';
      let nCards = n.args.n;

      let targetPouch = null;
      if (n.args.pouch) {
        if (n.args.pouch == 'mapPouched') {
          targetPouch = $(`zoo-map-${n.args.player_id}`).querySelector('.map-infos');
        } else {
          targetPouch = $(`card-${n.args.pouch}`);
        }
      }

      if (!this.isFastMode()) {
        await Promise.all(
          Array.from(Array(nCards), (x, i) => i).map((i) => {
            return this.wait(100 * i).then(() => {
              this.addZooCard({ id: i, fake: true }, `counter-${n.args.player_id}-${counter}`);
              let target = targetPouch != null ? targetPouch : this.getVisibleTitleContainer();
              return this.slide(`card-${i}`, target, {
                duration: this.getTiming(1000),
                destroy: true,
                phantom: false,
              });
            });
          })
        );
      }

      if (n.args.pouch) {
        targetPouch.dataset.pouch = parseInt(targetPouch.dataset.pouch || 0) + nCards;
      }
      this._playerCounters[n.args.player_id][counter].incValue(-nCards);
      this._discardCounter.incValue(nCards);
      if (n.args.bonuses) {
        await this.notif_getBonuses(n);
      }
    },

    /**
     * pilferingCard : slighty different => move card to other player panel and destroy it
     */
    async notif_pilferingCard(n) {
      if (n.args.player_id == this.player_id || n.args.player_id2 == this.player_id) {
        return;
      }

      let counter = 'handCount';
      let nCards = 1;
      if (this.isFastMode()) {
        this._playerCounters[n.args.player_id][counter].incValue(-nCards);
        this._playerCounters[n.args.player_id2][counter].incValue(nCards);
        return;
      }

      this.addZooCard({ id: 1, fake: true }, `counter-${n.args.player_id}-${counter}`);
      await this.slide(`card-1`, `counter-${n.args.player_id2}-${counter}`, {
        duration: this.getTiming(1000),
        destroy: true,
        phantom: false,
      });

      this._playerCounters[n.args.player_id][counter].incValue(-nCards);
      this._playerCounters[n.args.player_id2][counter].incValue(nCards);
    },

    /**
     * Public notification when discarding cards from the display
     */
    async notif_discardCardsOnDisplay(n) {
      debug('Notif: discarding cards on the display', n);

      // Remove tokens on the card
      if (n.args.tokenIds) {
        n.args.tokenIds.forEach((mId) => {
          $(`meeple-${mId}`).remove();
        });
      }

      if (this.isFastMode()) {
        n.args.cards.forEach((card) => {
          this.destroy($(`card-${card.id}`));
        });
        return;
      }

      await Promise.all(
        n.args.cards.map((card, i) => {
          return this.slide(`card-${card.id}`, this.getVisibleTitleContainer(), {
            delay: this.getTiming(100 * i),
            duration: this.getTiming(1000),
            destroy: true,
            phantom: false,
          });
        })
      );
      this._discardCounter.incValue(n.args.cards.length);
    },

    /**
     * Public notification when the pool get refilled
     *  slide existing cards to the left and create new cards
     */
    async notif_fillPool(n) {
      debug('Notif: refilling pool of cards', n);
      await Promise.all(
        n.args.pool.map((card) => {
          if (!$(`card-${card.id}`)) {
            this.addZooCard(card, this.getVisibleTitleContainer());
          }

          let container = this.getCardContainer(card);
          if ($(`card-${card.id}`).parentNode != container) {
            this._deckCounter.incValue(-1);
            return this.slide(`card-${card.id}`, container, {
              phantom: false,
              duration: this.getTiming(1000),
            });
          } else {
            return true;
          }
        })
      );
    },

    /**
     * Private notif when storing a card
     */
    async notif_pStoreCard(n) {
      debug('Notif: private storing card', n);
      let card = n.args.cards[0];
      let container = this.getCardContainer(card);

      if (this.isFastMode()) {
        container.insertAdjacentElement('beforeend', $(`card-${card.id}`));
      } else {
        let to = null;
        if (!isVisible(container)) to = $('floating-hand-button');

        const slidingDuration = this.getTiming(1000);
        await this.slide(`card-${card.id}`, container, {
          duration: slidingDuration,
          to,
        });
      }

      this._playerCounters[this.player_id]['handCount'].incValue(-1);
      this.updateCardCosts();
    },

    async notif_storeCard(n) {
      debug('Notif: public storing card', n);

      this._playerCounters[n.args.player_id]['handCount'].incValue(-1);
    },

    async notif_pUnstoreCard(n) {
      debug('Notif: private unstoring card', n);
      let card = n.args.cards[0];
      let container = this.getCardContainer(card);

      if (this.isFastMode()) {
        container.insertAdjacentElement('beforeend', $(`card-${card.id}`));
      } else {
        let to = null;
        if (!isVisible(container)) to = $('floating-hand-button');

        const slidingDuration = this.getTiming(1000);
        await this.slide(`card-${card.id}`, container, {
          duration: slidingDuration,
          to,
        });
      }

      this._playerCounters[this.player_id]['handCount'].incValue(1);
    },

    async notif_unstoreCard(n) {
      debug('Notif: public unstoring card', n);

      this._playerCounters[n.args.player_id]['handCount'].incValue(1);
    },

    //////////////////////////////////////////////
    //     _          _                 _
    //    / \   _ __ (_)_ __ ___   __ _| |___
    //   / _ \ | '_ \| | '_ ` _ \ / _` | / __|
    //  / ___ \| | | | | | | | | | (_| | \__ \
    // /_/   \_\_| |_|_|_| |_| |_|\__,_|_|___/
    //////////////////////////////////////////////

    tplAnimalCard(card, tooltip = false) {
      let uid = (tooltip ? 'tooltip_card-' : 'card-') + card.id;
      let req = card.enclosureRequirements;
      let enclosureCostClass = Object.keys(req).length > 0 ? 'wide' : '';
      let enclosures = [];
      if (card.enclosureSize > 0) {
        let enclosureType = card.noRegularEnclosure ? 'not-regular' : 'regular';
        let types = ['', ...Array(req.Rock || 0).fill('rock'), ...Array(req.Water || 0).fill('water')];
        enclosures.push(
          `<div class="arknova-icon icon-enclosure-${enclosureType}${types.join('-')}">${card.enclosureSize}</div>`
        );
      }
      if (card.specialEnclosure.types) {
        card.specialEnclosure.types.forEach((type) => {
          enclosures.push(`<div class="arknova-icon icon-enclosure-special-${type}">${card.specialEnclosure.cubes}</div>`);
        });
        enclosureCostClass = 'wide';
      }

      let badges = [];
      card.categories.forEach((cat) =>
        badges.push(`<div class='zoo-card-badge'><div class='badge-icon' data-type='${cat}'></div></div>`)
      );
      card.continents.forEach((cat) =>
        badges.push(`<div class='zoo-card-badge'><div class='badge-icon' data-type='${cat}'></div></div>`)
      );

      let prerequisites = [];
      Object.keys(card.prerequisites).forEach((cat) => {
        if (cat == 'reputation') {
          prerequisites.push(
            `<div class='zoo-card-badge'><div class='badge-icon' data-type='Reputation'>${card.prerequisites[cat]}</div></div>`
          );
          return;
        }

        for (let i = 0; i < card.prerequisites[cat]; i++) {
          prerequisites.push(`<div class='zoo-card-badge'><div class='badge-icon' data-type='${cat}'></div></div>`);
        }
      });

      let bonuses = [];
      ['reputation', 'conservation', 'appeal'].forEach((bonus) => {
        if (card[bonus] > 0) {
          bonuses.push(`<div class='zoo-card-bonus ${bonus}'>${card[bonus]}</div>`);
        }
      });

      let interactiveTokens = '';
      let tokensMap = { Venom: 'venom', Pilfering: 'pilfering', Hypnosis: 'hypnosis', Constriction: 'constriction' };

      let oAbilities = this.gamedatas.peaceful ? card.soloAbility : card.ability;
      let abilities = Object.keys(oAbilities).map((ability) => {
        let descs = this.getAbilityDesc(ability, oAbilities[ability], card);
        if (tokensMap[ability]) {
          let type = tokensMap[ability];
          interactiveTokens += `<div class="arknova-meeple arknova-icon icon-${type}" data-type="${type}"></div>`;
        }

        return `<div class='animal-ability'>
          <h6 class='animal-ability-title'>${descs.title}</h6>
          <div class='animal-ability-desc'>${descs.desc}</div>
        </div>`;
      });

      // MW
      let extensionIcon = '';
      if (card.supported.length == 1 && card.supported.includes('mw')) {
        extensionIcon = `<div class='ark-card-exp-icon'>
            <svg><use href="#mw-svg" /></svg>
          </div>`;
      }

      let wave = card.wave ? `<div class='ark-card-wave'>${this.formatIcon('wave')}</div>` : '';
      let reefAbilityExtraClass = '';
      let reefAbilities = Object.entries(card.reefAbility).map(([ability, n]) => {
        let descs = this.getAbilityDesc(ability, n, card);

        // Ability desc
        const NO_DESC = ['appeal', 'money', 'reputation', 'conservation', 'take-in-range-or-deck'];
        if (!NO_DESC.includes(ability)) {
          abilities.push(`<div class='animal-ability'>
          <h6 class='animal-ability-title'>${this.formatIcon('coral')} ${descs.title}</h6>
          <div class='animal-ability-desc'>${descs.desc}</div>
        </div>`);
        }

        // Icon
        let icon = '';
        if (ability == 'Trade') {
          icon = this.formatIcon('xtoken-bordered') + ':' + this.formatIcon('money', 5);
          reefAbilityExtraClass = 'trade';
        } else if (ability == 'Posturing') {
          icon = this.formatIcon('kiosk-or-pavilion');
        } else if (ability == 'Inventive') {
          icon = this.formatIcon('xtoken-bordered');
        } else if (ability == 'Boost') {
          icon = this.formatIcon('strength', 1) + this.formatIcon(`action-card-${n}`) + this.formatIcon('strength', 5);
          reefAbilityExtraClass = 'boost';
        } else if (ability == 'take-in-range-or-deck') {
          icon = this.formatIcon(ability);
        } else if (ability == 'ExtraShift') {
          icon = this.formatIcon('extra-shift-bordered');
        } else if (ability == 'SharkAttack') {
          icon = this.formatIcon('shark-attack', n);
        } else if (ability == 'Helpful') {
          icon = '';
        } else {
          icon = this.formatIcon(ability, n);
        }
        return icon;
      });
      let reefAbility = '';
      if (reefAbilities.length > 0) {
        reefAbility = `<div class='ark-card-reef-ability ${reefAbilityExtraClass}'>
          ${reefAbilities.join('')}
        </div>`;
      }

      let reduced = tooltip && card.reducedCost != undefined;

      return (
        `<div id="${uid}" data-id='${card.id}' class='ark-card zoo-card animal-card ${tooltip ? 'tooltip' : ''}'>
      <div class='ark-card-wrapper'>
        <div class='ark-card-top'>
          ${interactiveTokens}
          <div class='ark-card-top-left'>
            <div class='animal-card-enclosure-cost ${enclosureCostClass}'>
              <div class='animal-card-enclosure'>${enclosures.join('')}</div>
              <div class='animal-card-cost ${reduced ? 'reduced' : ''}'>
                <div class='arknova-icon icon-money original-cost'>${card.cost}</div>
                <div class='reduced-cost'>
                  <div class="arknova-icon icon-money">${reduced ? card.reducedCost : card.cost}</div>
                </div>
              </div>
            </div>
            <div class='zoo-card-prerequisites'>${prerequisites.join('')}</div>
          </div>
          <div class='ark-card-top-right'>
            ${badges.join('')}
            ${reefAbility}  
          </div>
        </div>
        <div class='ark-card-middle'>
          ${extensionIcon}
          <div class='ark-card-number'>${card.number}</div>
          <div class='ark-card-title-wrapper'>
            <div class='ark-card-title'>${_(card.name)}</div>
            <div class='ark-card-subtitle'>${_(card.latin)}</div>
          </div>
        </div>
        <div class='ark-card-bottom'>
          ${wave}
          ` +
        (bonuses.length == 0 ? '' : `<div class='zoo-card-bonuses' data-size='${bonuses.length}'>${bonuses.join('')}</div>`) +
        `
          ${abilities.join('')}
        </div>
      </div>
    </div>`
      );
    },

    getAbilityDesc(ability, n, card) {
      let descs = {
        Sprint: [_('Sprint ${n}'), _('Draw ${n} cards from the deck.'), _('Draw 1 card from the deck.')],
        Hunter: [
          _('Hunter ${n}'),
          _('Reveal the ${n} topmost cards of the deck. Choose 1 Animal card and add it to your hand. Discard the other cards.'),
          _('Reveal the topmost card of the deck. If it is an Animal, add it to your hand, otherwise discard it.'),
        ],
        Inventive: [_('Inventive'), _('Gain ${n} <XTOKEN>.'), _('Gain 1 <XTOKEN>.')],
        Jumping: [_('Jumping ${n}'), _('Advance the break token ${n} spaces. Gain <MONEY:${n}>')],

        Sunbathing: [_('Sunbathing ${n}'), _('You may sell up to ${n} cards from your hand for <MONEY:4> each')],
        Pouch: [
          _('Pouch ${n}'),
          _('You may place ${n} cards from your hand under this card to gain <APPEAL:2> (each).'),
          _('You may place 1 card from your hand under this card to gain <APPEAL:2>.'),
        ],
        FlockAnimal: [
          _('Flock Animal ${n}'),
          _('May share the existing enclosure of a <HERBIVORE> with <ENCLOSURE-REGULAR:${n}+>'),
        ],
        Digging: [
          _('Digging ${n}'),
          _(
            'Choose up to ${n}x: Discard 1 card from the display and replenish OR discard 1 card from your hand to draw 1 from the deck.'
          ),
          _('Discard 1 card from the display and replenish OR discard 1 card from your hand and draw 1 other from the deck.'),
        ],
        Venom: [
          _('Venom ${n}'),
          _('Each player ahead of you on the Appeal track gains ${n} Venom tokens'),
          _('Every player ahead of you on the Appeal track gains 1 Venom token.'),
        ],
        Pilfering: [
          _('Pilfering ${n}'),
          _(
            'From both the player with highest <APPEAL> and the player with the most <CONSERVATION>: Draw 1 card from the hand of or take <MONEY:5> from that player. They decide.'
          ),
          _('Draw 1 card from the hand of or take <MONEY:5> from the player with the most <APPEAL>. They decide.'),
        ],
        Snapping: [
          _('Snapping ${n}'),
          _('${n}x: Gain any 1 card from the display. You may replenish in between.'),
          _('Gain any 1 card from the display.'),
        ],
        Hypnosis: [
          _('Hypnosis ${n}'),
          _(
            'After finishing this action, you may perform 1 action from an Action Card in slot <STRENGTH:1>, <STRENGTH:2> or <STRENGTH:3> of the player with the most <APPEAL>, unless that is you'
          ),
        ],
        Scavenging: [
          _('Scavenging ${n}'),
          _('Shuffle the discard pile and draw ${n} cards from it. Keep 1 and discard the others.'),
        ],
        Posturing: [
          _('Posturing ${n}'),
          _('Up to ${n}x: You may place 1 free kiosk or pavilion'),
          _('You may place 1 free kiosk or pavilion'),
        ],
        Perception: [_('Perception ${n}'), _('Draw ${n} cards from the deck. Keep 2 and discard the others.')],
        Pack: [_('Pack'), _('Gain <APPEAL:1> for each predator icon in your zoo.')],
        Clever: [_('Clever'), _('After finishing the current action, you may place any Action Card on <STRENGTH:1>.')],
        Boost: [
          _('Boost: ${n}'),
          _(
            'After finishing the current action, you may place your __${n}__ Action card ${icon} on <STRENGTH:1> or <STRENGTH:5>.'
          ),
        ],
        Action: [_('Action: ${n}'), _('After finishing the current action, you may perform the __${n}__ action ${icon}.')],
        Multiplier: [_('Multiplier: ${n}'), _('Place 1 <MULTIPLIER> on your __${n}__ Action card ${icon}.')],
        FullThroated: [_('Full-throated'), _('Hire 1 association worker.')],
        IconicAnimal: [_('Iconic Animal: ${n}'), _('Gain <APPEAL:1> for each ${n} icon in all zoos (max.8).')],
        Resistance: [_('Resistance'), _('Draw 2 Final Scoring cards. Keep 1 and discard the other.')],
        Assertion: [_('Assertion'), _('You may add any 1 of the unused base conservation projects to your hand.')],
        SponsorMagnet: [_('Sponsor Magnet'), _('Add all sponsors cards from the display to your hand.')],
        Constriction: [
          _('Constriction'),
          _(
            'Each player ahead of you on a track gains 1 Constriction token for each of those tracks (<APPEAL> and <CONSERVATION>)'
          ),
        ],
        Determination: [_('Determination'), _('After finishing the current action, perform 1 additional action.')],
        Peacocking: [
          _('Peacocking'),
          _('You may place a Large Bird Aviary <ENCLOSURE-SPECIAL-LARGE-BIRD-AVIARY> for free, if possible.'),
        ],
        PettingZooAnimal: [_('Petting Zoo Animal'), _('Gain <APPEAL:3> for each Petting Zoo Animal icon in your zoo.')],
        Dominance: [
          _('Dominance'),
          _('You may add the base conservation project Primates to your hand if it is not already in the game.'),
        ],

        Adapt: [
          _('Adapt ${n}'),
          _('Draw ${n} new Final Scoring cards, then discard ${n} Final Scoring cards.'),
          _('Draw 1 Final Scoring card, then discard 1 Final Scoring card.'),
        ],
        Camouflage: [_('Camouflage'), _('If you play 1 other animal during this turn, you may ignore 1 of its conditions.')],
        CutDown: [_('Cut Down'), _('You may remove 1 empty standard enclosure from your zoo map and gain back its cost.')],
        ExtraShift: [_('Extra Shift'), _('You may return 1 of your association workers to your notepad.')],
        Glide: [
          _('Glide ${n}'),
          _('Discard up to ${n} cards from your hand: For every <SEAANIMAL> icon on them, gain 1 of these 3 effects:') +
            '<span class="glide-icons"><REPUTATION:1>, <APPEAL:2>, <KIOSK></span>',
        ],
        Helpful: [_('Helpful'), _('No Reef Dweller effect of its own.')],
        Mark: [_('Mark'), _('After finishing this action, mark 1 Animal card in the display.')],
        Marketing: [
          _('Marketing'),
          _('You may play a Sponsor card from your hand by paying X money, where X is the level of the card.'),
        ],
        MonkeyGang: [
          _('Monkey Gang'),
          _('Reveal cards from the deck. Take the first card with <PRIMATE> into your hand. Tuck the other cards under the deck'),
        ],
        ScubaDive: [
          _('Scuba Dive X'),
          _(
            'Reveal the X topmost cards of the deck. Choose 1 Sponsor card and add it to your hand. Discard the other cards. X is the number of <SEAANIMAL>+<REPTILE> in your zoo.'
          ),
        ],
        SeaAnimalMagnet: [_('Sea Animal Magnet'), _('Add all cards with the <SEAANIMAL> icon from the display to your hand.')],
        SharkAttack: [
          _('Shark Attack ${n}'),
          _(
            'Discard ${n} Animal cards in reputation range from the display: Gain half the <APPEAL> of those cards (round down).'
          ),
          _('Discard 1 Animal card in reputation range from the display: Gain half the <APPEAL> of that card (round down).'),
        ],
        Symbiosis: [_('Symbiosis'), _('Use 1 ability (not a Reef Dweller effect) of 1 other <SEAANIMAL> card in your zoo.')],
        Trade: [_('Trade'), _('You may trade exactly 1 <XTOKEN>-Marker for <MONEY:5> or viceversa.')],
      };

      // TODO TMP : remove when complete
      if (!descs[ability]) {
        return {
          title: ability + 'TODO',
          desc: 'TODO',
        };
      }

      let desc = descs[ability];
      let result = {
        title: desc[0],
        desc: n == 1 && desc.length > 2 ? desc[2] : desc[1],
      };

      if (ability == 'Inventive') {
        let inventiveDesc = {
          A411_GrizzlyBear: _('Gain 1<XTOKEN>-token for each bear icon in all zoos (max.3).'),
          A459_RedshankedDouc: _('Gain 1/2/3<XTOKEN>-tokens for 1/3/5 primate icons in your zoo.'),
          A464_BrownSpiderMonkey: _('Gain 1/2/3<XTOKEN>-tokens for 1/3/5 primate icons in your zoo.'),
        };
        if (card && inventiveDesc[card.id]) {
          result.desc = inventiveDesc[card.id];
        }
      }

      if (n !== null) {
        result.title = this.fsr(result.title, { i18n: ['n'], n });
        result.desc = this.fsr(result.desc, { i18n: ['n'], n, icon: this.formatIcon(`action-card-${n}`) });
      } else {
        result.desc = this.formatString(result.desc);
      }
      return result;
    },

    async notif_buyAnimal(n) {
      debug('Notif: buying an animal', n);

      // Pay appeal in case of release
      if (n.args.release) {
        await this.animatePlayerCounter(n.args.player_id, 'appeal', -n.args.amount);
      }
      // Or pay money in case of buy
      else {
        await this.animatePlayerCounter(n.args.player_id, 'money', -n.args.amount);
      }

      // Slide the card
      let id = `card-${n.args.card.id}`;
      if (!$(id)) {
        this.addZooCard(n.args.card, 'page-title');
      } else {
        $(id).classList.remove('selectedToMap10');
      }
      let container = this.getCardContainer(n.args.card);
      let config = { duration: this.getTiming(1000) };
      if (!isVisible(container)) config.to = $(`overall_player_board_${n.args.card.pId}`);
      await this.slide(id, container, config);
      if (!n.args.fromDisplay) {
        this._playerCounters[n.args.player_id]['handCount'].incValue(-1);
      }

      // Update enclosure
      let buildings = n.args.buildings;
      if (buildings) {
        buildings.forEach((building) => {
          let buildingId = `building-${building.id}`;
          if ($(buildingId)) {
            $(buildingId).dataset.state = building.state;
          }
        });
      }
    },

    async notif_releaseAnimal(n) {
      debug('Notif: release an animal', n);
      // Loose appeal
      await this.animatePlayerCounter(n.args.player_id, 'appeal', -n.args.amount);

      // Update score
      this._scoreCounters[n.args.player_id].toValue(n.args.score);

      // Slide the card
      if (this.isFastMode()) {
        $(`card-${n.args.card.id}`).remove();
      } else {
        await this.slide(`card-${n.args.card.id}`, this.getVisibleTitleContainer(), {
          duration: this.getTiming(1000),
          destroy: true,
          phantom: false,
        });
      }
      this._discardCounter.incValue(1);

      // Update enclosure
      let buildings = n.args.buildings;
      if (buildings) {
        buildings.forEach((building) => {
          if (building.type == 'empty') return;

          let buildingId = `building-${building.id}`;
          $(buildingId).dataset.state = building.state;
        });
      }
    },

    notif_moveAnimal(n) {
      debug('Notif: moving an animal', n);

      let building = n.args.building;
      $(`building-${building.id}`).dataset.state = building.state;

      // Update free enclosure
      let buildings = n.args.buildings;
      if (buildings) {
        buildings.forEach((building) => {
          if (building.type == 'empty') return;

          let buildingId = `building-${building.id}`;
          $(buildingId).dataset.state = building.state;
        });
      }

      return this.wait(1300);
    },

    notif_hypnosis(n) {
      return this.wait(800);
    },

    async notif_markAssign(n) {
      debug('Notification assign card linked to Mark', n);
      let cards = n.args.cards;
      const slidingDuration = this.getTiming(1000);

      // Current player => add to hand
      if (this.player_id == n.args.player_id) {
        await Promise.all(
          cards.map((card, i) => {
            // Remove token

            $(`card-${card.id}`)
              .querySelectorAll('.icon-token')
              .forEach((o) => o.remove());

            this.slide(`card-${card.id}`, this.getCardContainer(card), {
              duration: slidingDuration,
            });
          })
        );
      }
      // Otherwise, slide to counter
      else {
        await Promise.all(
          cards.map((card, i) =>
            this.slide(`card-${card.id}`, `counter-${n.args.player_id}-handCount`, {
              duration: slidingDuration,
              destroy: true,
              phantom: false,
            })
          )
        );
      }

      // Update counters
      this._playerCounters[n.args.player_id]['handCount'].incValue(cards.length);
    },

    //////////////////////////////////////////////
    //  ____            _           _
    // |  _ \ _ __ ___ (_) ___  ___| |_ ___
    // | |_) | '__/ _ \| |/ _ \/ __| __/ __|
    // |  __/| | | (_) | |  __/ (__| |_\__ \
    // |_|   |_|  \___// |\___|\___|\__|___/
    //               |__/
    //////////////////////////////////////////////

    tplProjectCard(card, tooltip = false, duplicated = false) {
      let uid = (tooltip ? 'tooltip_card-' : duplicated ? 'duplicated_card-' : 'card-') + card.id;
      const isRelease = card.category == 'release';
      let slots = card.slots.map((slot, i) => {
        let conservationGain = this.formatIcon('conservation', slot.gain.conservation);
        let repGain = slot.gain.reputation ? this.formatIcon('reputation', slot.gain.reputation) : '';

        let noBackground = ['release', 'breed'].includes(card.category);
        let slotIndicator = slot.condition == undefined || isObject(slot.condition) ? '' : slot.condition;
        if (card.category == 'release') {
          slotIndicator = this.formatIcon(`animal-size-${slot.condition.size}`);
        }

        let token = '';
        if (duplicated && $(`card-${card.id}_${i}`).firstChild) {
          let meeple = $(`card-${card.id}_${i}`).firstChild.cloneNode(true);
          meeple.id += 'fake';
          token = meeple.outerHTML;
        }
        return `<div class='project-card-slot'>
          <div class='project-card-slot-indicator ${card.category}'>${slotIndicator}</div>
          <div id='${uid}_${i}' class='project-card-slot-cube-holder ${noBackground ? '' : 'badge-icon'}' data-type='${
            card.icon
          }'>${token}</div>
          <div class='project-card-slot-reward'>${conservationGain}${repGain}</div>
        </div>`;
      });

      let subicons = {
        release: 'release-animal',
        breed: 'partner-zoo',
      };
      let subicon = subicons[card.category] ? subicons[card.category] : '';

      let bonuses = '';
      if (isRelease) {
        bonuses = `<div class='zoo-card-bonuses' data-size='1'><div class='zoo-card-bonus reputation'>1</div></div>`;
      }

      let extensionIcon = '';
      if (card.supported.length == 1 && card.supported.includes('mw')) {
        extensionIcon = `<div class='ark-card-exp-icon'>
            <svg><use href="#mw-svg" /></svg>
          </div>`;
      }

      // MW PROJECTS
      if (card.category == 'plan') {
        let getIcon = (bonus, n) => {
          if (bonus == 'SEARCH_CARD') {
            return this.formatIcon(`search-${n == 'SeaAnimal' ? 'sea-animal' : n}`);
          } else if (bonus == 'reputation') {
            return this.formatIcon('reputation-for-science');
          } else if (bonus == 'Hunter') {
            return this.formatIcon('Hunter');
          } else if (bonus == 'Reef') {
            return this.formatIcon('reef-short');
          } else if (bonus == 'kiosk-pavilion') {
            return this.formatIcon('kiosk-or-pavilion');
          } else if (bonus == 'Clever') {
            return this.formatIcon('clever');
          } else {
            return this.formatIcon(bonus, n);
          }
        };

        let playBonus = Object.entries(card.playedBonus)[0];
        if (card.id == 'P138_SeaAnimalManagementPlan') {
          bonuses = `<div class='zoo-card-bonuses' data-size='1'><div class='zoo-card-bonus reputation'>1</div></div>`;
        } else {
          bonuses = `<div class='zoo-card-bonuses' data-size='1'><div class='zoo-card-bonus'>${getIcon(playBonus[0], playBonus[1])}</div></div>`;
        }

        subicon = card.icon;
        slots = card.slots.map((slot, i) => {
          let conservationGain = '';
          let otherGain = '';

          Object.entries(slot.gain).forEach(([bonus, n]) => {
            if (bonus == 'conservation') {
              conservationGain = this.formatIcon('conservation', n);
            } else {
              otherGain = getIcon(bonus, n);
            }
          });

          let token = '';
          if (duplicated && $(`card-${card.id}_${i}`).firstChild) {
            let meeple = $(`card-${card.id}_${i}`).firstChild.cloneNode(true);
            meeple.id += 'fake';
            token = meeple.outerHTML;
          }
          return `<div class='project-card-slot'>
            <div id='${uid}_${i}' class='project-card-slot-cube-holder'>${token}</div>
            <div class='project-card-slot-reward'>
              ${conservationGain}
              <div class='project-other-gain'>
                ${otherGain}
              </div>
            </div>
          </div>`;
        });
      }

      return `<div id="${uid}" data-id='${card.id}' data-asset='${card.asset || card.id}'
        class='ark-card zoo-card project-card project-${card.category} ${tooltip ? 'tooltip' : ''}'>
      <div class='ark-card-wrapper'>
        <div class='ark-card-top'>
          <div class='ark-card-top-left'>
            <div class='project-card-top-left-icon ${subicon == '' ? '' : 'wide'}'>
              ${this.formatIcon(card.icon)}
              ${subicon == '' ? '' : this.formatIcon(subicon)}
            </div>
          </div>
          <div class='ark-card-top-right'></div>
        </div>
        <div class='ark-card-middle'>
          ${extensionIcon}
          <div class='ark-card-number'>${card.number}</div>
          <div class='ark-card-title-wrapper'>
            <div class='ark-card-title'>${_(card.name)}</div>
          </div>
        </div>
        <div class='ark-card-bottom'>
          <div class='project-card-description'>${this.formatString(_(card.desc))}</div>
          ${bonuses}
          <div class='project-card-slots-container ${card.category}'>
            ${slots.join('')}
          </div>
        </div>
      </div>
    </div>`;
    },

    async notif_moveProjects(n) {
      debug('Notif: inserting project & moving other cards', n);
      const slidingDuration = 1300;

      await Promise.all(
        n.args.projects.map((card) => {
          if ($(`card-${card.id}`)) {
            return this.slide(`card-${card.id}`, this.getCardContainer(card), {
              phantom: false,
              changeParent: false,
              duration: slidingDuration,
            });
          } else {
            this.addZooCard(card);
            return this.slide(`card-${card.id}`, this.getCardContainer(card), {
              from: `counter-${n.args.player_id}-handCount`,
              phantom: false,
              changeParent: false,
              duration: slidingDuration,
            });
          }
        })
      );
      if (!n.args.fromDisplay) {
        this._playerCounters[n.args.player_id]['handCount'].incValue(-1);
      }
    },

    //////////////////////////////////////////////
    //  ____
    // / ___| _ __   ___  _ __  ___  ___  _ __ ___
    // \___ \| '_ \ / _ \| '_ \/ __|/ _ \| '__/ __|
    //  ___) | |_) | (_) | | | \__ \ (_) | |  \__ \
    // |____/| .__/ \___/|_| |_|___/\___/|_|  |___/
    //       |_|
    //////////////////////////////////////////////

    tplSponsorCard(card, tooltip = false) {
      let uid = (tooltip ? 'tooltip_card-' : 'card-') + card.id;
      let tooltipDesc = '';
      if (tooltip) {
        tooltipDesc = "<ul class='sponsor-effects-list'>";
        Object.keys(card.effects).forEach((type) => {
          let desc = card.effects[type].map((t) => this.formatString(_(t))).join(' ');
          if (desc == '') desc = 'TODO';
          tooltipDesc += `<li class='effect-${type}'>${desc}</li>`;
        });

        if (card.id == 'S274_VictoryColumn') {
          tooltipDesc += '<li class="no-bullet"><hr/><b>' + _('Arena champions:') + '</b><ul>';
          Object.keys(winners).forEach((year) => {
            tooltipDesc += '<li>' + this.fsr(_('Season ${n}: ${winner}'), { n: year, winner: winners[year] }) + '</li>';
          });
          tooltipDesc += '</ul></li>';
        }

        tooltipDesc += '</ul>';
      }

      let choice = !tooltip && card.extraDatas && card.extraDatas.choice ? `data-choice="${card.extraDatas.choice}"` : '';

      let margin = '';
      if (['S251_PolarBearExhibit'].includes(card.id)) margin = 'double-margin';

      let suptitle = '';
      if (card.id == 'S274_VictoryColumn') {
        let maxYear = Math.max.apply(Math, Object.keys(winners));
        suptitle =
          '<div class="ark-card-suptitle">' +
          this.fsr(_('Arena Season ${n} champion: ${winner}'), { n: maxYear, winner: winners[maxYear] }) +
          '</div>';
      }

      let extensionIcon = '';
      if (card.supported.length == 1 && card.supported.includes('mw')) {
        extensionIcon = `<div class='ark-card-exp-icon'>
            <svg><use href="#mw-svg" /></svg>
          </div>`;
      }

      let wave = card.wave ? `<div class='ark-card-wave'>${this.formatIcon('wave')}</div>` : '';

      return (
        `<div id="${uid}" data-id='${card.id}' 
          class='ark-card zoo-card sponsor-card ${margin} ${tooltip ? 'tooltip' : ''}' ${choice}>
      <div class='ark-card-wrapper'>
        <div class='ark-card-top'>
          <div class='ark-card-top-left'>
            <div class='sponsor-card-top-left-icon'>
              ${this.formatIcon('strength', card.lvl)}
            </div>
          </div>
          <div class='ark-card-top-right'></div>
        </div>
        <div class='ark-card-middle'>
          ${extensionIcon}
          ${suptitle}
          <div class='ark-card-number'>${card.number}</div>
          <div class='ark-card-title-wrapper'>
            <div class='ark-card-title'>${_(card.name)}</div>
          </div>
        </div>
        <div class='ark-card-bottom'>
          ${wave}
        </div>
      </div>
    </div>` + tooltipDesc
      );
    },

    async notif_playSponsor(n) {
      debug('Playing sponsor', n);
      // Slide the card
      let id = `card-${n.args.card.id}`;
      if (!$(id)) {
        this.addZooCard(n.args.card, 'page-title');
      }
      let container = this.getCardContainer(n.args.card);
      let config = {};
      if (!isVisible(container)) config.to = $(`overall_player_board_${n.args.card.pId}`);
      await this.slide(id, container, config).then(() => this.updateCardCosts());
      if (!n.args.fromDisplay) {
        this._playerCounters[n.args.player_id]['handCount'].incValue(-1);
      }

      if (n.args.meeples) {
        await this.slideResources(
          n.args.meeples,
          {
            from: this.getVisibleTitleContainer(),
          },
          false
        );
      }
    },

    notif_wazaSpecial(n) {
      debug('Playing Waza Special', n);
      $('card-S227_WazaSpecialAssignment').dataset.choice = n.args.type;
      return this.wait(800);
    },

    //////////////////////////////////////////////
    //  ____                 _
    // / ___|  ___ ___  _ __(_)_ __   __ _ ___
    // \___ \ / __/ _ \| '__| | '_ \ / _` / __|
    //  ___) | (_| (_) | |  | | | | | (_| \__ \
    // |____/ \___\___/|_|  |_|_| |_|\__, |___/
    //                               |___/
    //////////////////////////////////////////////
    tplScoringCard(card, tooltip = false) {
      let uid = (tooltip ? 'tooltip_card-' : 'card-') + card.id;

      let scoreMap = '';
      if (card.id == 'F004_ArchitecturalZoo') {
        scoreMap = '<br/>';
        scoreMap += this.formatString(_('<CONSERVATION:1> for connecting **all water spaces.**')) + '<br />';
        scoreMap += this.formatString(_('<CONSERVATION:1> for connecting **all rock spaces.**')) + '<br />';
        scoreMap += this.formatString(_('<CONSERVATION:1> for building on **all border spaces.**')) + '<br />';
        scoreMap += this.formatString(_('<CONSERVATION:1> for completely covering **the zoo map.**'));
      } else if (card.id == 'F009_DiverseSpeciesZoo' || card.id == 'F017_InternationalZoo') {
      }
      // Normal case : scoreMap grid
      else {
        scoreMap += "<table class='score-map'><tr>";
        scoreMap += `<th>${this.formatIcon(card.icon)}</th>`;
        Object.keys(card.scoreMap).forEach((val) => (scoreMap += `<td>${val}</td>`));
        scoreMap += '</tr><tr><th></th>';
        Object.keys(card.scoreMap).forEach((val) => (scoreMap += `<td></td>`));
        scoreMap += '</tr><tr>';
        scoreMap += `<th>${this.formatIcon('CONSERVATION')}</th>`;
        Object.values(card.scoreMap).forEach((val) => (scoreMap += `<td>${val}</td>`));
        scoreMap += '</tr><table>';
      }

      // Special case for DiverseSpeciesZoo
      let desc = this.formatString(_(card.desc));
      if (card.id == 'F009_DiverseSpeciesZoo' && card.pId) {
        let prevPId = this.getDeltaPlayer(card.pId, -1);
        desc = this.formatString(
          this.fsr(
            _(
              'Gain <CONSERVATION:1> for each **animal category icon** of which you have more than the player before you in turn order : ${player_name}. Max. <CONSERVATION:4>'
            ),
            { player_name: this.gamedatas.players[prevPId].name }
          )
        );
      }

      let extensionIcon = '';
      if (card.supported.length == 1 && card.supported.includes('mw')) {
        extensionIcon = `<div class='ark-card-exp-icon'>
            <svg><use href="#mw-svg" /></svg>
          </div>`;
      }

      return `<div id="${uid}" data-id='${card.id}' data-asset='${card.asset || card.id}' 
        class='ark-card zoo-card scoring-card ${tooltip ? 'tooltip' : ''}'>
      <div class='ark-card-wrapper'>
        <div class='ark-card-top'>
          <div class='ark-card-top-left'>
            <div class='project-card-top-left-icon'>
              ${this.formatIcon('endgame')}
              ${this.formatIcon(card.icon)}
            </div>
          </div>
          <div class='ark-card-top-right'></div>
        </div>
        <div class='ark-card-middle'>
          ${extensionIcon}
          <div class='ark-card-status'></div>
          <div class='ark-card-number'>${String(card.number).padStart(3, '0')}</div>
          <div class='ark-card-title-wrapper'>
            <div class='ark-card-title'>${_(card.name)}</div>
          </div>
        </div>
        <div class='ark-card-bottom'>
          <div class='project-card-description'>
            ${desc}
            ${scoreMap}
          </div>
        </div>
      </div>
    </div>`;
    },

    onEnteringStateDiscardScoring(args) {
      if (!this.isCurrentPlayerActive()) {
        return;
      }

      if (this.isFloatingHand()) {
        $('floating-hand-wrapper').dataset.open = 'scoringHand';
      }

      let cardIds = args._private.cardIds;
      this.onSelectNCards(
        cardIds,
        {
          class: 'selectedToDiscard',
          confirmText: _('Confirm Discard'),
          n: 1,
          callback: (selectedElements) => {
            this.takeAtomicAction('actDiscardScoring', [selectedElements[0]]);
          },
        },
        'scoringHand'
      );
    },

    onUpdateActivityDiscardScoring(args, status) {
      dojo.empty('customActions');
    },

    ///////////////////////////////////////////////
    //  ____  _        _
    // / ___|| |_ __ _| |_ _   _ ___  ___  ___
    // \___ \| __/ _` | __| | | / __|/ _ \/ __|
    //  ___) | || (_| | |_| |_| \__ \  __/\__ \
    // |____/ \__\__,_|\__|\__,_|___/\___||___/
    ///////////////////////////////////////////////

    getCardStatus(cardId) {
      if (!this.cardStatuses[cardId]) return '';

      let card = this.getCardInfos(cardId);
      let status = this.cardStatuses[cardId];

      if (card.type == 'animal') {
        let html = "<ul class='card-status'>";
        let getIcon = (bool) => (bool ? ' <i class="fa fa-check"></i>' : ' <i class="fa fa-times"></i>');

        // Waza special
        if (status.wazaSpecial !== undefined) {
          html += `<li>${_('Waza Special Assignment:')}${getIcon(status.wazaSpecial)}</li>`;
        }

        // Enclosure
        if (status.enclosure !== undefined) {
          html += `<li>${_('Enclosure:')}${getIcon(status.enclosure)}
          <ul>`;

          // Requirement for enclosures
          let req = card.enclosureRequirements;
          let reqDesc = '';
          if ((req.Rock || 0) > 0) reqDesc += req.Rock + ' <span class="badge-icon" data-type="Rock"></span>';
          if ((req.Water || 0) > 0) reqDesc += req.Water + ' <span class="badge-icon" data-type="Water"></span>';

          if (card.enclosureSize > 0 && !card.noRegularEnclosure) {
            let enclosureDesc = this.fsr(_('Regular size-${n} enclosure'), { n: card.enclosureSize });
            if (reqDesc != '') enclosureDesc += ` (${_('next to')} ${reqDesc})`;
            html += `<li>${enclosureDesc}: ${getIcon(status.enclosures.regular || false)}</li>`;
          }

          if (card.specialEnclosure.types) {
            const specialEnclosureNames = {
              'reptile-house': _('Reptile House'),
              'large-bird-aviary': _('Large Bird Aviary'),
              'petting-zoo': _('Petting Zoo'),
              aquarium: _('Aquarium'),
            };

            card.specialEnclosure.types.forEach((type) => {
              let specialEnclosureDesc = specialEnclosureNames[type];
              if (reqDesc != '') specialEnclosureDesc += ` (${_('next to')} ${reqDesc})`;
              html += `<li>${specialEnclosureDesc}: ${getIcon(status.enclosures[type])}</li>`;
            });
          }
          if (status.canFlock !== undefined && Object.keys(card.ability).includes('FlockAnimal')) {
            html += `<li>${_('Flock:')}${getIcon(status.canFlock)}</li>`;
          }

          html += '</ul></li>';
        }

        // Cost
        if (status.cost !== undefined) {
          // In hand => only one cost
          if (status.folder == 0 || !status.baseCost) {
            html += `<li>${_('Cost:')}${getIcon(status.baseCost)}</li>`;
          } else {
            if (status.cost && status.baseCost) {
              html += `<li>${_('Cost:')}${getIcon(status.cost)}<br />
              ${this.formatString(
                this.fsr(_('(additional cost of <MONEY:${n}> if played from the display)'), { n: status.folder })
              )}
            </li>`;
            } else {
              html += `<li>${_('Cost:')}${getIcon(status.baseCost)} / ${getIcon(status.cost)}
              <ul>
                <li>${_('From hand:')}${getIcon(status.baseCost)}</li>
                <li>${this.fsr(_('From display (additional cost of <MONEY:${n}>)'), { n: status.folder })}: ${getIcon(
                  status.cost
                )}</li>
              </ul>
            </li>`;
            }
          }
        }

        // Conditions
        let prereq = Object.keys(card.prerequisites);
        if (status.conditions !== undefined && prereq.length > 0) {
          html += `<li>${_('Conditions:')}${getIcon(status.conditions.valid)} ${
            status.conditions.valid && status.conditions.ignored > 0 ? '*' : ''
          } <ul>`;
          prereq.forEach((cat) => {
            let n = card.prerequisites[cat];
            let type = cat == 'reputation' ? 'Reputation' : cat;
            html += `<li>${n > 1 ? `${n} ` : ''}<div class='badge-icon' data-type='${type}'></div>: 
            ${getIcon(status.conditions[cat])}
            ${!status.conditions[cat] && cat == 'Partner-Zoo' ? _("(partner zoo must match animal's continent)") : ''}  
          </li>`;
          });

          html += '</ul></li>';
        }

        html += '</ul>';
        return html;
      } else {
        return '';
      }
    },

    updateCardStatuses() {
      Object.keys(this.cardStatuses).forEach((cardId) => {
        let card = this.getCardInfos(cardId);
        let status = this.cardStatuses[cardId];

        if (card.type == 'scoring') {
          let container = $(`card-${cardId}`).querySelector('.ark-card-status');
          let bonusIcon = this.formatIcon('CONSERVATION', status.bonus);
          if (card.id == 'F004_ArchitecturalZoo') {
            container.innerHTML = `${this.formatIcon('CONSERVATION', status.qty[0] ? 1 : 0)} + 
              ${this.formatIcon('CONSERVATION', status.qty[1] ? 1 : 0)} + 
              ${this.formatIcon('CONSERVATION', status.qty[2] ? 1 : 0)} + 
              ${this.formatIcon('CONSERVATION', status.qty[3] ? 1 : 0)}`;
          } else {
            container.innerHTML = `${status.qty} ${this.formatIcon(card.icon)}  : ${bonusIcon}`;
          }
        }
      });
    },
  });
});
