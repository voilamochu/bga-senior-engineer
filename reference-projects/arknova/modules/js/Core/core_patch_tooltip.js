var debugTooltips = window.location.hash.indexOf('debugTooltips') > -1 ? console.info.bind(window.console) : function () {};
define([
  'dojo',
  'dojo/_base/declare',
  'dojo/dom-geometry',
  'dojo/_base/lang',
  'dojo/_base/array',
  'dojo/dom',
  'dojo/mouse',
  'dojo/on',
  'dojo/dom-style',
  'dijit/Tooltip',
  'dijit/place',
  'dojo/_base/kernel', // kernel.deprecated
  'dojo/_base/window', // win.body
  'dijit/Viewport', // getEffectiveBox
  'dojo/sniff',
], function (dojo, declare, domGeometry, lang, array, dom, mouse, on, domStyle, Tooltip, place, kernel, win, Viewport, has) {
  return declare('ebg.core.core_patch_tooltip', null, {
    constructor: function () {
      _patchTooltipPosIfNeeded.call(this);
      function _patchTooltipPosIfNeeded() {
        const positionFctToBePatch = domGeometry.position;
        function noop() {}
        function _masterTT_postCreate() {
          this.ownerDocumentBody.appendChild(this.domNode);

          this.bgIframe = new BackgroundIframe(this.domNode);

          // Setup fade-in and fade-out functions.
          this.fadeIn = fx.fadeIn({ node: this.domNode, duration: this.duration, onEnd: lang.hitch(this, '_onShow') });
          this.fadeOut = fx.fadeOut({ node: this.domNode, duration: this.duration, onEnd: lang.hitch(this, '_onHide') });
        }
        function getBoundingSize(element) {
          var cstyle = window.getComputedStyle(element);
          var borderWidth = parseInt(cstyle.borderWidth);
          var w = element.scrollWidth + 2 * borderWidth;
          var h = element.scrollHeight + 2 * borderWidth;
          return { w, h };
        }
        function _geom_position(/*DomNode*/ node, /*Boolean?*/ includeScroll) {
          function calcCurrentCSSZoom(node) {
            if (typeof node.currentCSSZoom !== 'undefined') return node.currentCSSZoom;
            let zoom = 1.0;
            var zoomStr = window.getComputedStyle(node).getPropertyValue('zoom');

            if (zoomStr != '') {
              zoom = parseFloat(zoomStr);
            }
            if (node != document.body) {
              const parent = node.parentElement;
              if (parent) zoom = zoom * calcCurrentCSSZoom(parent);
            }
            return zoom;
          }
          if (typeof node == 'string') {
            node = document.getElementById(node);
          }
          var zoom;
          if (_bCorrPos && (zoom = calcCurrentCSSZoom(node)) != 1) {
            let position = positionFctToBePatch(node, false);
            position.x = position.x * zoom;
            position.y = position.y * zoom;
            if (includeScroll) {
              if (_bCorrScroll) {
                position.x += window.pageXOffset * zoom;
                position.y += window.pageYOffset * zoom;
              } else {
                position.x += window.pageXOffset;
                position.y += window.pageYOffset;
              }
            }
            position.w = position.w * zoom;
            position.h = position.h * zoom;
            return position;
          }
          return positionFctToBePatch(node, includeScroll);
        }
        function _masterTT_Show(innerHTML, aroundNode, position, rtl, textDir, onMouseEnter, onMouseLeave) {
          debugTooltips('_masterTT_Show', aroundNode.id);

          const origPositionFct = domGeometry.position;
          try {
            // summary:
            //        Display tooltip w/specified contents to right of specified node
            //        (To left if there's no space on the right, or if rtl == true)
            // innerHTML: String
            //        Contents of the tooltip
            // aroundNode: DomNode|dijit/place.__Rectangle
            //        Specifies that tooltip should be next to this node / area
            // position: String[]?
            //        List of positions to try to position tooltip (ex: ["right", "above"])
            // rtl: Boolean?
            //        Corresponds to `WidgetBase.dir` attribute, where false means "ltr" and true
            //        means "rtl"; specifies GUI direction, not text direction.
            // textDir: String?
            //        Corresponds to `WidgetBase.textdir` attribute; specifies direction of text.
            // onMouseEnter: Function?
            //        Callback function for mouse enter on tooltip
            // onMouseLeave: Function?
            //        Callback function for mouse leave on tooltip
            if (this.aroundNode && this.aroundNode === aroundNode && this.containerNode.innerHTML == innerHTML) {
              return;
            }

            if (this.fadeOut.status() == 'playing') {
              // previous tooltip is being hidden; wait until the hide completes then show new one
              this._onDeck = arguments;
              return;
            }
            function posTooltip() {
              debugTooltips('posTooltip', aroundNode.id);
              this.showTimeoutId = 0;
              domGeometry.position = _geom_position;
              try {
                this.containerNode.offsetWidth;
                this.containerNode.firstChild.offsetWidth;
                if (textDir) {
                  this.set('textDir', textDir);
                }

                var pos = place.around(
                  this.domNode,
                  aroundNode,
                  position && position.length ? position : Tooltip.defaultPosition,
                  !rtl,
                  lang.hitch(this, 'orient')
                );

                // Position the tooltip connector for middle alignment.
                // This could not have been done in orient() since the tooltip wasn't positioned at that time.
                var aroundNodeCoords = pos.aroundNodePos;
                if (pos.corner.charAt(0) == 'M' && pos.aroundCorner.charAt(0) == 'M') {
                  this.connectorNode.style.top =
                    aroundNodeCoords.y + ((aroundNodeCoords.h - this.connectorNode.offsetHeight) >> 1) - pos.y + 'px';
                  this.connectorNode.style.left = '';
                } else if (pos.corner.charAt(1) == 'M' && pos.aroundCorner.charAt(1) == 'M') {
                  this.connectorNode.style.left =
                    aroundNodeCoords.x + ((aroundNodeCoords.w - this.connectorNode.offsetWidth) >> 1) - pos.x + 'px';
                } else {
                  // Not *-centered, but just above/below/after/before
                  this.connectorNode.style.left = '';
                  this.connectorNode.style.top = '';
                }

                resizeToFit(this.containerNode, pos.corner, this.aroundNode, this.connectorNode, this.domNode);
                this.fadeIn.play();
              } finally {
                domGeometry.position = origPositionFct;
                document.body.style.overflowX = overflowXOrig;
              }
            }
            this.domNode.style = '';
            //this.containerNode.style.width = "fit-content";
            this.containerNode.offsetWidth;
            var overflowXOrig = document.body.style.overflowX;
            var overflowX = window.getComputedStyle(document.body).overflowX;
            if (overflowX !== 'hidden') document.body.style.overflowX = 'hidden';
            this.containerNode.innerHTML = innerHTML;
            this.isShowingNow = true;
            this.containerNode.style = '';
            this.connectorNode.style = '';
            // this.containerNode.style.maxWidth =  window.getComputedStyle(this.containerNode.firstChild).getPropertyValue("max-width");
            // add a timmeout to let the time to the system to load the imsages contain in the tooltip
            // otherwhise the tooltip is too small
            this.showTimeoutId = setTimeout(posTooltip.bind(this), 100);
            domStyle.set(this.domNode, 'opacity', 0);
            this.aroundNode = aroundNode;

            this.onMouseEnter = onMouseEnter || noop;
            this.onMouseLeave = onMouseLeave || noop;
          } finally {
            // domGeometry.position = origPositionFct;
          }
        }
        function _masterTT_hide(aroundNode) {
          // summary:
          //        Hide the tooltip
          debugTooltips('_masterTT_hide', aroundNode.id);
          if (this._onDeck && this._onDeck[1] == aroundNode) {
            // this hide request is for a show() that hasn't even started yet;
            // just cancel the pending show()
            this._onDeck = null;
          } else if (this.aroundNode === aroundNode) {
            if (this.showTimeoutId) clearTimeout(this.showTimeoutId);
            // this hide request is for the currently displayed tooltip
            this.fadeIn.stop();
            this.isShowingNow = false;
            this.aroundNode = null;
            this.fadeOut.play();
          } else {
            // just ignore the call, it's for a tooltip that has already been erased
          }

          this.onMouseEnter = this.onMouseLeave = noop;
        }
        function _masterTT_onHide() {
          // summary:
          //		Called at end of fade-out operation
          // tags:
          //		protected

          this.domNode.style.cssText = ''; // to position offscreen again
          this.containerNode.innerHTML = '';
          this.containerNode.style = '';
          if (this._onDeck) {
            // a show request has been queued up; do it now
            this.show.apply(this, this._onDeck);
            this._onDeck = null;
          }
        }
        function resizeToFit(containerNode, tooltipCorner, aroundNode, connectorNode, domNode) {
          var containerSize = getBoundingSize(containerNode);
          domGeometry.setMarginBox(containerNode, { w: containerSize.w });
          domNode.style.width = 'auto';

          containerNode.style.transform = '';
          containerNode.style.transformOrigin = '';
          // Reduce tooltip's width to the amount of width available, so that it doesn't overflow screen.
          // Note that sometimes widthAvailable is negative, but we guard against setting style.width to a
          // negative number since that causes an exception on IE.

          var containerRect = _geom_position(containerNode);
          var domRect = _geom_position(domNode, true);

          var aroundNodePos = _geom_position(aroundNode, true);
          var view = Viewport.getEffectiveBox(document);
          if (tooltipCorner.charAt(0) == 'B' && domRect.y + domRect.h > aroundNodePos.y) {
            domRect.y = aroundNodePos.y - domRect.h;
            domNode.style.top = domRect.y + 'px';
          }
          if (tooltipCorner.charAt(1) == 'R' && domRect.x + domRect.w > aroundNodePos.x) {
            domRect.x = aroundNodePos.x - domRect.w;
            domNode.style.left = domRect.x + 'px';
          }

          var containerRect = _geom_position(containerNode, true);
          if (has('ie') || has('trident')) {
            // workaround strange IE bug where setting width to offsetWidth causes words to wrap
            containerRect.w += 2;
          }
          var heightAvailable;
          switch (tooltipCorner.charAt(0)) {
            case 'B':
              heightAvailable = containerRect.y + containerRect.h - view.t;
              break;
            case 'M':
              heightAvailable = view.h;
              break;
            default:
              heightAvailable = view.t + view.h - containerRect.y;
          }
          var widthAvailable;
          switch (tooltipCorner.charAt(1)) {
            case 'L':
              widthAvailable = view.l + view.w - containerRect.x;
              break;
            case 'M':
              widthAvailable = view.w;
              break;
            default:
              widthAvailable = containerRect.x + containerRect.w - view.l;
          }

          var scale = 1;
          if (containerRect.w > widthAvailable) scale = Math.min(scale, widthAvailable / containerRect.w);
          if (containerRect.h > heightAvailable) scale = Math.min(scale, heightAvailable / containerRect.h);
          if (scale < 1) {
            var transformX, transformY;
            switch (tooltipCorner.charAt(0)) {
              case 'B':
                transformY = 'bottom';
                break;
              case 'M':
                transformY = 'top';
                break;
              default:
                transformY = 'top';
            }
            switch (tooltipCorner.charAt(1)) {
              case 'R':
                transformX = 'right';
                break;
              case 'M':
                transformX = 'left';
                break;
              default:
                transformX = 'left';
            }
            containerNode.style.transform = `scale(${scale})`;
            containerNode.style.transformOrigin = `${transformX} ${transformY}`;

            debugTooltips('pos.corner', tooltipCorner);
          }
          var width = Math.min(Math.max(widthAvailable, 1), domRect.w);
          domGeometry.setMarginBox(domNode, { w: width });
        }
        function _masterTT_orient(
          /*DomNode*/ node,
          /*String*/ aroundCorner,
          /*String*/ tooltipCorner,
          /*Object*/ spaceAvailable,
          /*Object*/ aroundNodeCoords
        ) {
          // summary:
          //        Private function to set CSS for tooltip node based on which position it's in.
          //        This is called by the dijit popup code.   It will also reduce the tooltip's
          //        width to whatever width is available
          // tags:
          //        protected
          this.connectorNode.style = ''; //reset to default

          var heightAvailable = spaceAvailable.h,
            widthAvailable = spaceAvailable.w;

          node.className =
            'dijitTooltip ' +
            {
              'MR-ML': 'dijitTooltipRight',
              'ML-MR': 'dijitTooltipRight',
              'TM-BM': 'dijitTooltipBelow',
              'BM-TM': 'dijitTooltipBelow',
              'BL-TL': 'dijitTooltipBelow dijitTooltipABLeft',
              'TL-BL': 'dijitTooltipBelow dijitTooltipABLeft',
              'BR-TR': 'dijitTooltipBelow dijitTooltipABLeft',
              'TR-BR': 'dijitTooltipBelow dijitTooltipABLeft',
              'BR-BL': 'dijitTooltipRight',
              'BL-BR': 'dijitTooltipRight',
            }[aroundCorner + '-' + tooltipCorner];

          // reset width; it may have been set by orient() on a previous tooltip show()
          this.domNode.style.width = 'auto';

          var containerRect = getBoundingSize(this.domNode);

          debugTooltips('containerRect', containerRect);
          var width = Math.min(Math.max(widthAvailable, 1), containerRect.w);

          node.className =
            'dijitTooltip ' +
            {
              'MR-ML': 'dijitTooltipRight',
              'ML-MR': 'dijitTooltipLeft',
              'TM-BM': 'dijitTooltipAbove',
              'BM-TM': 'dijitTooltipBelow',
              'BL-TL': 'dijitTooltipBelow dijitTooltipABLeft',
              'TL-BL': 'dijitTooltipAbove dijitTooltipABLeft',
              'BR-TR': 'dijitTooltipBelow dijitTooltipABRight',
              'TR-BR': 'dijitTooltipAbove dijitTooltipABRight',
              'BR-BL': 'dijitTooltipRight',
              'BL-BR': 'dijitTooltipLeft',
            }[aroundCorner + '-' + tooltipCorner];
          // Reduce tooltip's width to the amount of width available, so that it doesn't overflow screen.
          // Note that sometimes widthAvailable is negative, but we guard against setting style.width to a
          // negative number since that causes an exception on IE.
          /*var size = domGeometry.position(this.domNode);
                    if(has("ie") || has("trident")){
                        // workaround strange IE bug where setting width to offsetWidth causes words to wrap
                        size.w += 2
                    }*/
          domGeometry.setMarginBox(this.domNode, { w: width });

          // Reposition the tooltip connector.
          if (tooltipCorner.charAt(0) == 'B' && aroundCorner.charAt(0) == 'B') {
            var bb = getBoundingSize(this.domNode);
            // var bb = domGeometry.position(node);
            var tooltipConnectorHeight = this.connectorNode.offsetHeight;
            if (bb.h > heightAvailable) {
              // The tooltip starts at the top of the page and will extend past the aroundNode
              var aroundNodePlacement = heightAvailable - ((aroundNodeCoords.h + tooltipConnectorHeight) >> 1);
              this.connectorNode.style.top = aroundNodePlacement + 'px';
              this.connectorNode.style.bottom = '';
            } else {
              // Align center of connector with center of aroundNode, except don't let bottom
              // of connector extend below bottom of tooltip content, or top of connector
              // extend past top of tooltip content
              this.connectorNode.style.bottom =
                Math.min(Math.max(aroundNodeCoords.h / 2 - tooltipConnectorHeight / 2, 0), bb.h - tooltipConnectorHeight) + 'px';
              this.connectorNode.style.top = '';
            }
          } else {
            // reset the tooltip back to the defaults
            this.connectorNode.style.top = '';
            this.connectorNode.style.bottom = '';
          }

          var overflow = Math.max(0, containerRect.w - widthAvailable);
          debugTooltips('overflow', aroundCorner, overflow);
          return overflow;
        }
        function _setStateAttr(val) {
          if (
            this.state == val ||
            (val == 'SHOW TIMER' && this.state == 'SHOWING') ||
            (val == 'HIDE TIMER' && this.state == 'DORMANT')
          ) {
            return;
          }

          if (this._hideTimer) {
            this._hideTimer.remove();
            delete this._hideTimer;
          }
          if (this._showTimer) {
            this._showTimer.remove();
            delete this._showTimer;
          }
          debugTooltips(this._connectNode ? this._connectNode.id : this._connectIds, 'new state', val, 'prev state', this.state);
          switch (val) {
            case 'DORMANT':
              if (Tooltip.currentTooltip == this) {
                debugTooltips(
                  'currentTooltip state',
                  Tooltip.currentTooltip._connectNode
                    ? Tooltip.currentTooltip._connectNode.id
                    : Tooltip.currentTooltip._connectIds,
                  Tooltip.currentTooltip.state
                );
                Tooltip.currentTooltip = undefined;
                if (this._connectNode) {
                  Tooltip.hide(this._connectNode);
                  delete this._connectNode;
                  this.onHide();
                }
              }
              if (Tooltip.nextTooltip == this) {
                Tooltip.nextTooltip = undefined;
              }
              break;
            case 'SHOW TIMER': // set timer to show tooltip
              // Hide current tooltip if different from this tooltip
              if (Tooltip.currentTooltip != this) {
                if (Tooltip.currentTooltip) {
                  debugTooltips(
                    'currentTooltip state',
                    Tooltip.currentTooltip._connectNode
                      ? Tooltip.currentTooltip._connectNode.id
                      : Tooltip.currentTooltip._connectIds,
                    Tooltip.currentTooltip.state
                  );
                  if (Tooltip.currentTooltip.state == 'SHOWING') {
                    Tooltip.currentTooltip.set('state', 'HIDE TIMER');
                  }
                }
              }
              if (Tooltip.nextTooltip != this && Tooltip.nextTooltip) {
                if (Tooltip.nextTooltip.state == 'SHOW TIMER') {
                  Tooltip.nextTooltip.set('state', 'DORMANT');
                }
              }
              Tooltip.nextTooltip = this;
              // should only get here from a DORMANT state, i.e. tooltip can't be already SHOWING
              if (this.state != 'SHOWING') {
                this._showTimer = this.defer(function () {
                  this.set('state', 'SHOWING');
                }, this.showDelay);
              }
              break;
            case 'SHOWING': // show tooltip and clear timers
              var content = this.getContent(this._connectNode);
              if (!content) {
                this.set('state', 'DORMANT');
                return;
              }

              // Hide current tooltip if different from this tooltip
              if (Tooltip.currentTooltip != this) {
                if (Tooltip.currentTooltip) {
                  debugTooltips(
                    'currentTooltip state',
                    Tooltip.currentTooltip._connectNode
                      ? Tooltip.currentTooltip._connectNode.id
                      : Tooltip.currentTooltip._connectIds,
                    Tooltip.currentTooltip.state
                  );
                  Tooltip.currentTooltip.set('state', 'DORMANT');
                }
                Tooltip.currentTooltip = this;
              }
              if (Tooltip.nextTooltip != this && Tooltip.nextTooltip) {
                Tooltip.nextTooltip.set('state', 'DORMANT');
              }
              Tooltip.nextTooltip = undefined;

              // Show tooltip and setup callbacks for mouseenter/mouseleave of tooltip itself
              Tooltip.show(
                content,
                this._connectNode,
                this.position,
                !this.isLeftToRight(),
                this.textDir,
                lang.hitch(this, 'set', 'state', 'SHOWING'),
                lang.hitch(this, 'set', 'state', 'HIDE TIMER')
              );

              this.onShow(this._connectNode, this.position);
              break;
            case 'HIDE TIMER': // set timer set to hide tooltip
              this._hideTimer = this.defer(function () {
                this.set('state', 'DORMANT');
              }, this.hideDelay);
              break;
          }

          this._set('state', val);
        }
        /* patch to make tooltip displayed on safari ios*/
        function _setConnectIdAttr(/*String|String[]|DomNode|DomNode[]*/ newId) {
          // summary:
          //        Connect to specified node(s)

          // Remove connections to old nodes (if there are any)
          array.forEach(
            this._connections || [],
            function (nested) {
              array.forEach(nested, function (handle) {
                handle.remove();
              });
            },
            this
          );

          // Make array of id's to connect to, excluding entries for nodes that don't exist yet, see startup()
          this._connectIds = array.filter(
            lang.isArrayLike(newId) ? newId : newId ? [newId] : [],
            function (id) {
              return dom.byId(id, this.ownerDocument);
            },
            this
          );

          // Make connections
          this._connections = array.map(
            this._connectIds,
            function (id) {
              var t = null;
              var node = dom.byId(id, this.ownerDocument),
                selector = this.selector,
                delegatedEvent = selector
                  ? function (eventType) {
                      return on.selector(selector, eventType);
                    }
                  : function (eventType) {
                      return eventType;
                    },
                self = this; /* ,
                            target = node */
              return [
                // pointerover is prefferred over mouseover as on touch device releasing the pointer will trigger false mousenter.
                /*pointerover is the one to use
                            mouseover can not be use as on touch device releasing the pointer will trigger false mousenter.
                            Sp mousenter false events will break the expected behaviour that if a parent div is attach to a tooltip
                            and if a child div is attached to another tooltip you expect the child tooltip to show when you touch the child
                            Pointerenter doesnt have this problem but pointerenter will not fire again once you leave the child false events
                            and that will break the expected behaviour that parent tooltip should reappear again*/
                on(node, delegatedEvent('PointerEvent' in window ? 'pointerover' : 'mouseover'), function (e) {
                  e.stopPropagation();
                  debugTooltips(e.type, e.target.id);
                  var t2 = Date.now();
                  if (t == null || t2 - t > 300) {
                    self._onHover(this);
                  }
                }),
                on(node, delegatedEvent('focusin'), function (e) {
                  // e.stopPropagation();
                  debugTooltips(e.type, e.target.id);
                  self._onHover(this);
                }),
                /* begin of patch for safari ios */
                on(
                  node,
                  delegatedEvent('touchstart'),
                  function (e) {
                    //pointerdown
                    e.stopPropagation();
                    debugTooltips('touchstart', e.target.id);
                    if (e.touches.length === 1) {
                      t = Date.now();
                      // debugTooltips(e.type, e.target);
                      var hideFct = function (e) {
                        // debugTooltips("hideFct, close tooltip", e.type);
                        document.removeEventListener('touchstart', hideFct, true);
                        self.set('state', 'DORMANT');
                      };
                      var stopTimerFct = function (e) {
                        debugTooltips('stopTimerFct', e.type, e.target.id);
                        document.removeEventListener('touchcancel', stopTimerFct, true);
                        document.removeEventListener('touchend', stopTimerFct, true);
                        document.removeEventListener('pointerout', stopTimerFct, true);
                        if (self.state == 'SHOWING') {
                          //already showing, no time to stop
                          document.addEventListener('touchstart', hideFct, true);
                          // debugTooltips("monitor touchstart", e.type, e.target);
                        } else {
                          // stop timer
                          self.set('state', 'DORMANT');
                          // debugTooltips("close tooltip", e.type, e.target);
                        }
                      };
                      document.addEventListener('touchend', stopTimerFct, true);
                      document.addEventListener('touchcancel', stopTimerFct, true);
                      document.addEventListener('pointerout', stopTimerFct, true);
                      self._onHover(this);
                    }
                  },
                  true
                ),
                /* end of patch*/
                on(node, delegatedEvent('mouseleave'), function (e) {
                  e.stopPropagation();
                  debugTooltips(e.type, e.target.id);
                  self._onUnHover(this);
                }),
                on(node, delegatedEvent('focusout'), function (e) {
                  debugTooltips(e.type, e.target.id);
                  self._set('state', 'DORMANT');
                }),
              ];
            },
            this
          );

          this._set('connectId', newId);
        }
        function _onHover(/*DomNode*/ target) {
          // summary:
          //		Despite the name of this method, it actually handles both hover and focus
          //		events on the target node, setting a timer to show the tooltip.
          // tags:
          //		private
          if (
            typeof this.hoverEnabled !== 'undefined' &&
            (typeof this.hoverEnabled === 'function' ? !this.hoverEnabled() : !this.hoverEnabled)
          )
            return;
          if (this._connectNode && target != this._connectNode) {
            // Tooltip is displaying for another node
            this.set('state', 'DORMANT');
          }
          this._connectNode = target; // _connectNode means "tooltip currently displayed for this node"

          this.set('state', 'SHOW TIMER'); // no-op if show-timer already set, or if already showing
        }

        function _onUnHover(/*DomNode*/ target) {
          //		Handles mouseleave event on the target node, hiding the tooltip.
          // tags:
          //		private
          this.set('state', 'HIDE TIMER'); // no-op if already dormant, or if hide-timer already set
        }
        // open() and close() aren't used anymore, except from the _BidiSupport/misc/Tooltip test.
        // Should probably remove for 2.0, but leaving for now.
        function open(/*DomNode*/ target) {
          // summary:
          //        Display the tooltip; usually not called directly.
          // tags:
          //        private
          debugTooltips('Tooltip.open', target.id);
          if (this._connectNode && target != this._connectNode) {
            // Tooltip is displaying for another node
            this.set('state', 'DORMANT');
          }
          this._connectNode = target; // _connectNode means "tooltip currently displayed for this node"
          this.set('state', 'SHOWING');
        }
        const scrollX = window.pageXOffset;
        const scrollY = window.pageYOffset;
        const el = document.createElement('div');
        el.style = 'top : 10px; left: 10px; zoom: 2.0; width: 4000px; height: 4000px; position: absolute';
        document.body.appendChild(el);
        const el2 = document.createElement('div');
        el2.style = 'top : 20px; left: 5px; zoom: 4.0; width: 10px; height: 10px; position: absolute';
        el.appendChild(el2);
        window.scroll(0, 0);
        const tBox = domGeometry.position(el2);
        var _bCorrPos = tBox.x > 7.4 && tBox.x < 7.6;
        const _origPosition = domGeometry.position;
        var _bCorrScroll = false;
        debugTooltips('_checkIfPosCorrNeeded', _bCorrPos, tBox.x);
        Object.defineProperty(Tooltip._MasterTooltip.prototype, 'show', {
          get() {
            return _masterTT_Show;
          },
        });
        Object.defineProperty(Tooltip._MasterTooltip.prototype, 'orient', {
          get() {
            return _masterTT_orient;
          },
        });
        Object.defineProperty(Tooltip._MasterTooltip.prototype, 'hide', {
          get() {
            return _masterTT_hide;
          },
        });
        Object.defineProperty(Tooltip._MasterTooltip.prototype, '_onHide', {
          get() {
            return _masterTT_onHide;
          },
        });
        Object.defineProperty(Tooltip.prototype, '_setConnectIdAttr', {
          get() {
            return _setConnectIdAttr;
          },
        });
        Object.defineProperty(Tooltip.prototype, '_setStateAttr', {
          get() {
            return _setStateAttr;
          },
        });
        Tooltip.prototype._onHover = _onHover;
        Tooltip.prototype._onUnHover = _onUnHover;
        /*Object.defineProperty(Tooltip.prototype, "_onHover", {
                    get() {return _onHover;}
                });
                Object.defineProperty(Tooltip.prototype, "_onUnHover", {
                    get() {return _onUnHover;}
                });*/
        Object.defineProperty(Tooltip.prototype, 'open', {
          get() {
            return open;
          },
        });
        Tooltip.defaultPosition = ['after-centered', 'before-centered', 'below-centered', 'above-centered'];
        this.defaultPosition = ['after-centered', 'before-centered', 'below-centered', 'above-centered'];
        // place.around = around;
        Tooltip._checkIfPosCorrDone = true;
        if (_bCorrPos) {
          window.scroll(80, 0);
          const tBox2 = domGeometry.position(el2);
          _bCorrScroll = tBox2.x > -72.6 && tBox2.x < -72.4;
          debugTooltips('_checkIfPosCorrNeeded', _bCorrScroll, tBox2.x);
        }
        document.body.removeChild(el);
        window.scroll(scrollX, scrollY);
      }
    },
    addTooltipHtml: function (id, html, delay) {
      function hoverEnabled(/*DomNode*/ target) {
        var enabled = !this.bHideTooltips;
        debugTooltips('hoverEnabled', enabled);
        return enabled;
      }
      this.inherited('addTooltipHtml', arguments);
      var tooltip = this.tooltips[id];
      tooltip.position = ['after-centered', 'before-centered', 'below-centered', 'above-centered'];
      if (typeof tooltip.hoverEnabled === 'undefined') tooltip.hoverEnabled = hoverEnabled.bind(this);
    },
    addTooltip: function (id, html, delay) {
      function hoverEnabled(/*DomNode*/ target) {
        var enabled = !this.bHideTooltips;
        debugTooltips('hoverEnabled', enabled);
        return enabled;
      }
      this.inherited('addTooltip', arguments);
      var tooltip = this.tooltips[id];
      tooltip.position = ['after-centered', 'before-centered', 'below-centered', 'above-centered'];
      if (typeof tooltip.hoverEnabled === 'undefined') tooltip.hoverEnabled = hoverEnabled.bind(this);
    },
    addCustomTooltip: function (id, html, delay) {
      function hoverEnabled(/*DomNode*/ target) {
        var enabled = !this._helpMode && !this._dragndropMode && !this.bHideTooltips;
        debugTooltips('hoverEnabled', enabled);
        return enabled;
      }
      this.inherited('addCustomTooltip', arguments);
      var tooltip = this.tooltips[id];
      tooltip.position = ['after-centered', 'before-centered', 'below-centered', 'above-centered'];
      tooltip.set('connectId', id);
      if (typeof tooltip.hoverEnabled === 'undefined') tooltip.hoverEnabled = hoverEnabled.bind(this);
      var tooltipInfo = this.tooltipsInfos[id];
      if (tooltipInfo && tooltipInfo.hideOnHoverEvt) {
        dojo.disconnect(tooltipInfo.hideOnHoverEvt);
        tooltipInfo.hideOnHoverEvt = null;
      }
      tooltipInfo = this.tooltipsInfos[id] = {
        hideOnHoverEvt: null,
      };
      dojo.connect(
        tooltip,
        '_onHover',
        dojo.hitch(this, function () {
          if (tooltipInfo.hideOnHoverEvt === null && $('dijit__MasterTooltip_0')) {
            tooltipInfo.hideOnHoverEvt = dojo.connect($('dijit__MasterTooltip_0'), 'mouseenter', () => {
              debugTooltips('dijit__MasterTooltip_0 onmouseenter', 'close tooltip', tooltip.id);
              tooltip.close();
            });
          }
        })
      );
    },
    destroy(elem) {
      this.inherited('destroy', arguments);
      this.removeTooltip(elem.id);
    },
    removeTooltip: function (id) {
      this.inherited('removeTooltip', arguments);
      if (this.tooltips[id]) delete this.tooltips[id];
      if (this.tooltipsInfos) {
        var tooltipInfo = this.tooltipsInfos[id];
        if (tooltipInfo && tooltipInfo.hideOnHoverEvt) {
          dojo.disconnect(tooltipInfo.hideOnHoverEvt);
          tooltipInfo.hideOnHoverEvt = null;
        }
      }
    },
  });
});
