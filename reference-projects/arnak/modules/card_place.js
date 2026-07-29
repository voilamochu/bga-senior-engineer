function CardPlace (input_cards = []) {
  var cards = input_cards;

  return {
    add : function(card) {
      for (var c of cards) {
        if (card.id == c.id)
          return;
      }
      cards.push(card);
    },
    remove : function(id) {
      for (var i in cards) {
        if (cards[i].id == id) {
          return cards.splice(i, 1)[0];
        }
      }
    },
    size : function () {return cards.length;},
    foreach : function (f) {
      for (var card of cards) {
        f(card);
      }
    },
    rforeach : function (f) {
      for (var i = cards.length - 1; i >= 0; i--) {
        f(cards[i]);
      }
    },
    clear() {
      cards = [];
    }
  };
}

function BackCardPlace (num) {
  var cards = [];
  for (var i = 0; i < num; i++) {
    cards.push({type: "back"});
  }

  return {
    add : function(fromCard, topdeck = false) {
      card = {type: "back"};
      if (fromCard)
        card.fromDiv = fromCard.div;

      if(topdeck)
        cards.push(card);
      else
        cards.unshift(card);
    },
    remove : function(id, topdeck = true) {
      if (cards.length > 0) {
        if(topdeck)
          return cards.pop();
        else
          return cards.shift();
      }
    },
    size : function () {return cards.length;},
    foreach : function (f) {
      for (var card of cards) {
        f(card);
      }
    },
    clear() {
      cards = [];
    }
  };
}

function CardCounter (val = 0) {
  var count = val;
  var pendingDivs = [];
  return {
    add : function(card) { 
      if (card) {
        if (card.div)
          pendingDivs.push(card.div);
        if (card.fromDiv)
          pendingDivs.push(card.fromDiv);
      }
      count+=1;
    },
    remove : function() { count+=-1;},
    size : function() {return count;},
    foreach : function (f) {
      for (var i = 0; i<count; i++) {
        f(undefined);
      }
    },
    clear : function() {count = 0;},
    update : function(destroyFn) { 
      for(var div of pendingDivs)
        destroyFn(div);
      pendingDivs = [];
    }
  };
}