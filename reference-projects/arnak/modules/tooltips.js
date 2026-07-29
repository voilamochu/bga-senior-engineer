class Tooltips {
  constructor(material, siteBoxes, researchBoxes, birdTemple) {
    this.resources = {
      coins: {header: _("Coins"), text: _("Coins are mostly used to buy items.")},
      compass: {header: _("Compass"), text: _("Compasses are needed for discovering new locations and buying artifacts")},
      tablet: {header: _("Tablets"), text: _("Tablets are needed to play artifacts from hand and moving up the research track.")},
      arrowhead: {header: _("Arrowheads"), text: _("Most guardians require an arrowhead to overcome. They also are often needed to move up the research track.")},
      jewel: {header: _("Jewels"), text: _("Jewels are a rare resource needed for moving up the research track.")},
      idol: {header: _("Available idols"), text: _("Number of idols the player has available. You can make special free actions by moving an idol into an empty idol slot. At the end of the game, each idol, even if it was used for idol effect, is worth 3 points.")},
      idol_slot: {header: _("Empty idol slots"), text: _("Number of empty idol slots available for idol actions. 0/1/2/3/4 empty idol slots are worth 0/4/7/9/10 victory points at the end of the game.")},
      guardian: {header: _("Guardians overcame"), text: _("Guardians overcame throughout the game. Each overcame guardian is worth 5 victory points at the end of the game.")},
      handsize: {header: _("Cards in hand"), text: _("Cards left in the hand of the player. Cards can be used either for their effect or as travel symbols.")},
      meeple: {header: _("Archaeologists"), text: _("Archaeologists can be used to dig at sites to get their effect, or discover undiscovered locations. Each player has total of 2 archaeologists, and can never have more or less than that.")},
    };
    this.ressourceCostSingle = {
      "coins": _("coin"),
      "compass": _("compass"),
      "tablet": _("tablet"),
      "jewel": _("jewel"),
      "arrowhead": _("arrowhead"),
      "idol": _("idol"),
      "discard": _("card discard"),
      "fear": _("fear card")
    };
    this.ressourceCostSeveral = {
      "coins": _("coins"),
      "compass": _("compasses"),
      "tablet": _("tablets"),
      "arrowhead": _("arrowheads")
    };

    this.material = material;
    this.siteBoxes = siteBoxes;
    this.researchBoxes = researchBoxes;
    this.birdTemple = birdTemple;
    this.staff = {header: _("Moon Staff"), text: _("Moon staff is moved 1 slot to the right at the end of every round. Cards to the left of the staff are artifacts, while the cards to the right of the staff are items.")};
    this.startPlayer = {header: _("Starting player"), text: _("This player plays first in the current round. The token is passed to the next player at the end of each round.")};
  }
  costText(resources) {
    var texts = [];
    for (var resource in resources) {
      if (resource == "travel") {
        texts.push(this.travelIcons(resources[resource]));
      }
      else {
        var amount = resources[resource];
        texts.push(amount + " " + ((amount < 2) ? this.ressourceCostSingle[resource] : this.ressourceCostSeveral[resource]));
      }
    }
    return texts.join(", ");
  }
  travelIcons(travel) {
    var tokenText = "";
    for (var token of travel) {
      var travelStr = ""
      if (token == BOOT)
        travelStr = "boot";
      else if (token == SHIP)
        travelStr = "ship";
      else if (token == CAR)
        travelStr = "car";
      else if (token = PLANE)
        travelStr = "plane";
      tokenText += "<div class='token-tooltip-inline'><div class='token-tooltip-position'><div class='icon " + travelStr + "'></div></div></div>";
    }
    return tokenText;
  }
  cardName(type, num) {
    return this.material["cards"][type][num]["name"];
  }
  cardEffect(type, num) {
    var basicNames = {
      "fundship": _("Gain 1 coin as a free action"),
      "fundcar": _("Gain 1 coin as a free action"),
      "exploreship": _("Gain 1 compass as a free action"),
      "explorecar": _("Gain 1 compass as a free action"),
      "fear": _("This card cannot be played and doesn't have any effect.")
    };
    if (type == "basic") {
      return basicNames[num];
    }

    if (type == "art") {
      return ["",
      _("Relocate one of your placed archaeologists to a camp site and activate it."),
      _("Relocate one of your placed archaeologists to a camp or level 1 site and activate it."),
      _("Gain an arrowhead. Don't gain Fear cards from guardians this round."),
      _("Draw a card. Gain a coin."),
      _("You may exile a card. Gain an arrowhead."),

      _("Draw up to 3 cards and keep 1. You may return 1 of them to the top of your deck. Discard the rest. (You first decide how many cards to draw, then draw that many cards)"),
      _("You may exile a card. Gain 2 coins."),
      _("Gain a fear and 4 coins."),
      _("Gain a fear and a jewel."),
      _("Gain an item from the supply for free and place it on the top of your deck."),

      _("Research or get a temple tile with a discount of 1 jewel."),
      _("Research or get a temple tile with a discount of 1 arrowhead or 2 tablets."),
      _("Return one of your placed archaeologists to your board. Each of your travel icons counts as a plane this round."),
      _("Exile a card and activate an unoccupied camp site"),
      _("Overcome a guardian on a site with your archaeologist for free"),

      _("Gain 2 tablets or pass to gain a jewel."),
      _("Upgrade a resource and gain 3 coins."),
      _("Gain 1 fear and 2 arrowheads."),
      _("Gain 2 coins and use the effect on the silver side of one assistant available on the supply board."),
      _("Draw a card. Then, you may exile a card."),

      _("Gain 1 coin and use the effect on the gold side of one assistant available on the supply board."),
      _("Exchange one of your assistants with one available on the supply board. The new one is the same level and refreshed."),
      _("Exile the rightmost item card in the card row. Gain any item for free from exile to the bottom of your deck."),
      _("Pay a coin to activate any 2 different camp sites."),
      _("Draw a card."),

      _("Travel to a camp site for free. You may activate it twice instead of once."),
      _("Refresh one of your exhausted assistants."),
      _("Discard another card to refresh both your assistants."),
      _("Upgrade a resource and gain 2 coins"),
      _("Move one of your idols on your player board from a slot back into your supply crates."),

      _("Draw up to 2 cards from the bottom of your deck. Keep 1, discard the other."),
      _("Reveal the top tile of the level 1 stack, activate it, then put it on the bottom."),
      _("Pay a compass to reveal the top tile of the level 2 stack, activate it, then put it on the bottom."),
      _("Gain a fear, a coin and 3 tablets."),
      _("Move a guardian from a site you occupy to an unoccupied camp or level 1 site with no guardians. Activate that site.")

      ][num];
    }
    if (type == "item") {
      return ["",
      _("Draw a card, then explore or dig at a site with a discount of 1 ship."),
      _("Draw a card, then explore or dig at a site with a discount of 1 car."),
      _("Draw 2 cards."),
      _("Draw a card, gain a coin and a compass."),
      _("Gain 2 compasses as a free action."),

      _("Gain 2 compasses as a free action."),
      _("Gain a compass, then explore or dig at a site with a discount of 2 boots."),
      _("Gain 2 coins as a free action."),
      _("Pay a compass to gain a jewel."),
      _("Pay a compass to gain a tablet and an arrowhead."),

      _("Exile this card to explore or dig at a site with a discount of 1 plane - if discovering a new site, you also have a discount of 3 compasses."),
      _("Explore or dig at a site with a discount of 1 plane - if discovering a new site, you also have a discount of 2 compasses."),
      _("Exile this card to research with your notebook token for free (remember, your notebook can never be above the magnifying glass on the research track)."),
      _("Discard another card to gain a jewel."),
      _("Gain 2 coins as a free action, or pass to gain 3 coins."),

      _("Choose two different options: exile a card, gain a coin, gain a compass or gain a tablet"),
      _("Activate any level 1 site"),
      _("Activate a site you occupy. If it is a level 2 site, you must first pay 2 compasses."),
      _("Buy an item with a discount of 3 coins. Include the top card of the item deck."),
      _("Buy an artifact with a discount of 3 compasses. Include the top card of the artifact deck."),

      _("Gain a compass for each guardian on sites you occupy and each guardian you have overcome, up to 3."),
      _("Gain 2 tablets as a free action."),
      _("Exile this card to buy an artifact with a discount of 4 compasses."),
      _("Exile this card to gain 3 compasses."),
      _("Exile this card to gain an item from the general supply to your hand."),

      _("Exile this card to draw 3 cards."),
      _("Exile a card and gain 2 compasses"),
      _("Exile a card and gain 1 tablet"),
      _("Gain a coin and draw a card from the bottom of your deck."),
      _("Discard another card to draw 2 cards;"),

      _("Pay 1 compass to overcome a guardian for free on a site with your archaeologist."),
      _("Gain a coin and a compass as a free action."),
      _("Exile this card to overcome a guardian for free on a site not occupied by any other player (the site can be occupied by you)"),
      _("Discard another card to draw a card. Then, you may exile a card."),
      _("Activate any camp site."),

      _("Gain a compass. Activate any unoccupied camp site."),
      _("Gain a compass for each idol you have, up to 3. Both your free idols and idols in idol slots count."),
      _("You may exile a card. Gain compass."),
      _("Gain a coin and a compass as a free action OR pass to gain 3 compasses."),
      _("Gain a coin. Gain a compass for each of your archaeologists already placed on the board.")
      ][num];
    }
  }
  cardCost(type, num) {
    return this.material["cards"][type][num]["cost"];
  }
  cardPoints(type, num) {
    return this.material["cards"][type][num]["points"];
  }
  cardTravel(type, num) {
    return this.material["cards"][type][num]["travel"];
  }

  // btw, we need to return string from these functions, because makeTooltipHtml takes string as an argument
  resource(resName) {
    return "<h3>" + this.resources[resName].header + "</h3><div>" + this.resources[resName].text + "</div>";
  }
  card(type, num, color = "red") {
    var typeString = "";
    var classNames = "";
    if (type == "art") {
      typeString = " (" + _("artifact") + ")";
      classNames = type + " " + type + "-" + num;
    }
    if (type == "item") {
      typeString = " (" + _("item") + ")";
      classNames = type + " " + type + "-" + num;
    }
    if (type == "basic") {
      classNames = {
        "exploreship": "exploration ship",
        "explorecar": "exploration car",
        "fundship": "funding ship",
        "fundcar": "funding car",
        "fear": "fear",
      }[num];
    }

    var whenPlay = "";
    var playExplain = _("The card effect is triggered when you play it from hand.");
    if (type == "basic" && num == "fear") {
      playExplain = null;
    }
    if (type == "art") {
      playExplain = _("The card effect is triggered both when you play it from hand (which costs 1 tablet) and when you buy it from the card row.");
    }
    var travelString = this.travelIcons(this.cardTravel(type, num));
    var useTravel = this.fsr(_("You can instead use the card as ${travelString}. "), { travelString });
    if (type == "art") {
      useTravel += _("(You cannot use the travel symbol when you buy the card from the card row)");
    }
    var pointAmt = this.cardPoints(type, num);
    var pointString = this.fsr(_("This card is worth ${pointAmt} points at the end of the game if it is in your deck, hand or play area."), { pointAmt });

    return "<div class='card-tooltip'><h3>" + this.cardName(type, num) + typeString + "</h3>" +
        "<div class='effect-wrap'><ul><li>" + whenPlay + "<span class='effect'>" + this.cardEffect(type, num) + "</span></li>" +
        (playExplain ? "<li>" + playExplain + "</li>" : "") +
        "<li>" + useTravel + "</li>" +
        "<li>" + pointString + "</li></ul>" +
        "<div class='card front " + classNames + " " + color + "'></div>";
  }


  idolBonus(type) {
    return "<h2>" + _("Idol bonus") + "</h2><div>" + _("As a free action, you can put an idol into an empty idol slot and ") + {
      "jewel": _("pay a coin to get a jewel."),
      "arrowhead": _("gain 1 arrowhead."),
      "tablet": _("gain 2 tablets."),
      "coins": _("get 1 coin and 1 compass."),
      "card": _("draw a card."),
    }[type];
  }
  siteBox(locationId, site, guard) {
    var guardWrap = "";
    if (guard) {
      var guardDiv = "<div class='guardian guardian-" + guard.num + "'></div>";
      guardWrap = "<div class='guardian-wrap'>" + guardDiv + "</div>";
    }
    var siteDiv = "";
    if (site && site.size != "basic") {
      siteDiv = "<div class='location " + site.size + " location-" + site.num + "'></div>";
    }
    var box = this.siteBoxes[locationId];
    var x = (box.x / 100) * 2884;
    var y = (box.y / 100) * 4097;
    var w = (box.w / 100) * 2884;
    var h = (box.h / 100) * 2884 + 250;
    var bg = "<div class='site-bg " + (site ? site.size : "basic") + " " + (this.birdTemple ? "front" : "back") + "' style='background-position-x: -" + (x) + "px; background-position-y: -" + y + "px; width: " + w + "px; height: " + h + "px'></div>";
    var siteWrap = bg + "<div class='site-wrap'>" + siteDiv + guardWrap + "</div>";
    var boardWrap = "<div class='board-wrap'>" + siteWrap + "</div>";

    var compassNum = 3;
    if (box.size == "big") {
      compassNum = 6;
    }
    var header = _("Undiscovered dig site");
    var siteTravelCost = this.travelIcons(this.material.travelCost[locationId][0]);
    if (box.numSlots == 2)
      siteTravelCost += _(" or ") + this.travelIcons(this.material.travelCost[locationId][1]);

    var siteExplanation = this.fsr(_("As a main action, you can discover it by paying ${cost} and ${compassNum} compasses and moving one of your archaeologists here. You will get the idol bonus, the idol itself and the site effect, but will face a guardian."), { cost: siteTravelCost, compassNum: compassNum });
    if (site) {
      siteExplanation = this.fsr(_("As a main action, you can pay ${cost}, move one of your archaeologists on an empty space and evaluate the site effect."), { cost: siteTravelCost });
      header = _("Discovered dig site");
    }
    siteExplanation = "<li>" + siteExplanation + "</li>"
    var guardExplanation = "";
    if (guard) {
      guardExplanation = "<li>" + _("There is a guardian at this site. The site effect can still be used, but at the end of the round, you gain 1 fear card for each of your archaeologists at a site with a guardian.") + "</li>";
      guardExplanation += "<li>" + this.fsr(_("As a main action, if you have an archaeologist at this site, you can overcome the guardian by paying ${cost}. You will be able to use its boon once at any time throughout the game"), { cost: this.costText(this.material.guardians[guard.num].cost)}) + "</li>";
    }
    return "<div class='site-tooltip'><h3>" + header + "</h3><ul class='explanation'>" + siteExplanation + guardExplanation + "</ul>" + boardWrap + "</div>";
  }
  temple(id, amt) {
    var color = this.material.research.tiles[id].color;
    var colorText = {"gold": _("Golden"), "silver": _("Silver"), "bronze": _("Bronze")}[color];
    var score = this.material.research.tiles[id].points;
    var tileText = _("temple tile");
    var header = "<h3>" + colorText + " " + tileText + "</h3>";
    var resourcesText = this.costText(this.material.research.tiles[id].cost);

    var explanation = _("You can get temple tiles as a main action once you magnifying glass reaches the top of the research track.");

    return header + "<ul><li>" + explanation + "</li><li>" + _("This tile costs ") + resourcesText + "</li><li>" + this.fsr(_("This tile is worth ${score} points at the end of the game"), { score }) + "</li><li>" + this.fsr(_("There are ${amt} tiles left in this stack"), { amt }) + "</li>";
  }
  effectText(step, type) {
    var special = this.material.research.steps[step][type].bonus;
    var effectTexts = {
      "coins": _("gain a coin"),
      "compass": _("gain a compass"),
      "card": _("draw a card"),
      "2coins": _("gain 2 coins"),
      "fear": _("gain a fear card"),
      "jewel": _("gain a jewel"),
      "3compass": _("gain 3 compasses"),
      "free-art": _("get an artifact card for free"),
      "assistant-silver": _("get an assistant from the supply"),
      "assistant-gold": _("upgrade one assistant to gold side and refresh it"),
      "assistant-special": _("look through the stack of assistants on this space and choose one to take"),
      "assistant-refresh": _("refresh one assistant"),
      "exile": _("exile a card"),
      "guard": _("overcome a guardian for free on a site you occupy")
    }
    return effectTexts[special];
  }
  stepScore(step, type) {
    return this.material.research.steps[step][type].points;
  }
  research(id, bonuses) {
    var box = this.researchBoxes[this.birdTemple?0:1][id];
    var x = (box.x / 100) * 2884;
    var y = (box.y / 100) * 4097;
    var w = (box.w / 100) * 2884;
    var h = (box.h / 100) * 2884 + (id == 0 ? 30 : 160);

    var glassText = "<li>" + _("If a player reaches this space with their magnifying glass, they take the left-most free space and select 1 bonus token from the stack") + "</li>";
    var bookText = "";
    if (id <= 7) {
      var step = this.material.research.squares[id].step;
      glassText = "<li>" + _("When a player's magnifying glass reaches this space, they ") + this.effectText(step, "glass") + "</li>";
      bookText = "<li>" + _("When a player's notebook reaches this space, they ") + this.effectText(step, "book") + "</li>";
    }
    var bg = "<div class='research-bg " + (this.birdTemple ? "front" : "back") + "' style='background-position-x: -" + (x) + "px; background-position-y: -" + y + "px; width: " + w + "px; height: " + h + "px'></div>";
    var headerText = _("Research space");
    var generalExplanation = "<li>" +
    _("You can move a token to this space if it is on the space below, as a main action")+ "</li><li>" +
    _(" To move the token, you must pay ") + this.costText(this.material.research.squares[id].cost) + "</li><li>" +
    _("A player's notebook can never be higher than their magnifying glass") + "</li>";

    var tokenExplanation = "";
    if (bonuses.length == 1) {
      var tokenBonusText = {
        "coins": _("gains a coin"),
        "compass": _("gains a compass"),
        "tablet": _("gains a tablet"),
        "exile": _("may exile a card"),
        "upgrade": _("may upgrade a resource"),
        "card": _("draws a card"),
        "refresh": _("may refresh one of his assistants")}[bonuses[0].bonus_type];
      tokenExplanation = "<li>" + _("The first player to reach this space with either of their tokens ") + tokenBonusText + "</li>";
    }
    if (bonuses.length > 1) {
      tokenExplanation = "<li>" + _("A player who reaches this space selects one of available tokens") + "</li>";
    }
    if (id == 0) {
      tokenExplanation = tokenBonusText = glassText = bookText = "";
      headerText = _("Research starting space");
      generalExplanation = "<li>" + _("This is the starting space of the research track. All players' notebooks and magnifying glass start here") + "</li>";
    }
    return "<h3>" + headerText + "</h3><div class='research-tooltip research-tooltip-" + id + "'>" + bg + "<ul>" + generalExplanation + tokenExplanation + glassText  + bookText + "</ul></div>";
  }
  researchBonus(type, step) {
    var effectText = this.effectText(step, type);
    var typeText = type == "book" ? _("notebook") : _("magnifying glass");
    effectText = effectText[0].toUpperCase() + effectText.substr(1);
    return "<ul><li>" + effectText + "</li><li>" + _("This effect is triggered when a player reaches this row with their ") + typeText + "</li><li>" + _("If your ") + typeText + _(" is on this row at the end of the game, score ") + this.stepScore(step, type) + "</ul>";
  }
  assistant(num, gold, height) {
    var header = "<h3>" + _("Assistant") + "</h3>";
    var silverEffect = "<li>" + ["",
      _("Gain 2 coins"),
      _("Gain a tablet"),
      _("Pay boot travel icon to gain an arrowhead"),
      _("Pay coin to gain an arrowhead"),
      _("Exile a card"),
      _("Draw a card. Then, discard a card."),
      _("Gain a coin or use as a plane travel icon."),
      _("Gain a compass or use as a car travel icon."),
      _("Gain a compass or use as a ship travel icon."),
      _("As a main action, gain an item or artifact with discount of 1. This assistant cannot be used if you already played another main action this turn."),
      _("Upgrade a resource."),
      _("Gain a compass")
    ][num] + "</li>";
    var goldEffect = ["",
      _("Gain 3 coins"),
      _("Gain a coin and a tablet"),
      _("Gain an arrowhead"),
      _("Pay coin to gain an arrowhead or jewel"),
      _("Exile a card and gain a compass"),
      _("Draw a card"),
      _("Gain 2 coins or use as 2 plane travel icons"),
      _("Gain a compass and a coin or use as 2 car travel icons"),
      _("Gain a compass and a coin or use as 2 ship travel icons"),
      _("The discount is 2"),
      _("Upgrade a resource and gain a compass"),
      _("Gain 2 compasses")
    ][num];
    var generalExplanation = "<li>" + _("Assistants are gained and upgraded through effects on the research track.") + "</li><li>" + _("Each assistant can only be used once per round.") + "</li>";
    var stackHeight = "";
    if (height) {
      stackHeight = "<li>" + this.fsr(_("There are total of ${height} assistants in this stack."), { height });
    }
    var image = "<div class='assistant-inner " + (gold ? "gold" : "silver") + " assistant-" + num + "'>";

    return "<div class='ass-tooltip'>" + header + "<ul>" + (gold ?
      "<li>" + goldEffect + "</li>" :
      silverEffect + "<li>" + " " + this.fsr(_("If upgraded, ${effect} instead"), { effect : goldEffect.toLowerCase() }) + "</li>") + generalExplanation + stackHeight + "</ul>" + image + "</div>";
  }

  // COPY PASTE DEFINITION OF TRANSLATION FUNCTION TO AVOID INFINITE LOOPS
  fsr(log, args) {
    if (log === null) {
      console.error('format_string_recursive called with a null string with args:');
      console.error(args);
      return 'null_tr_string';
    }
    var result = '';
    if (log != '') {
      var _884 = this.clienttranslate_string(log);
      if (_884 === null) {
        this.showMessage('Missing translation for `' + log + '`', 'error');
        console.error('Missing translation for `' + log + '`', 'error');
        return '';
      }
      var i;
      var _885;
      if (typeof args.i18n != 'undefined') {
        for (i in args.i18n) {
          _885 = args.i18n[i];
          args[_885] = this.clienttranslate_string(args[_885]);
        }
      }
      for (_885 in args) {
        if ((_885 != 'i18n') && ((typeof args[_885]) == 'object')) {
          if (args[_885] !== null) {
            if ((typeof args[_885].log != 'undefined') && (typeof args[_885].args != 'undefined')) {
              args[_885] = fsr(args[_885].log, args[_885].args);
            }
          }
        }
      }
      try {
        result = dojo.string.substitute(_884, args);
      } catch (e) {
        if (typeof this.prevent_error_rentry == 'undefined') {
          this.prevent_error_rentry = 0;
        }
        this.prevent_error_rentry++;
        if (this.prevent_error_rentry >= 10) {
          console.error('Preventing error rentry => ABORTING');
        }
        this.prevent_error_rentry--;
        console.error('Invalid or missing substitution argument for log message: ' + _884, 'error');
        result = _884;
      }
    }
    return result;
  }
  clienttranslate_string(_886) {
    var _887 = _(_886);
    if (_887 == _886) {
      return __('lang_mainsite', _886);
    } else {
      return _887;
    }
  }
}
