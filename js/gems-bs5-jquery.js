/* ==========================================================================
   gems-bs5-jquery.js — jQuery <-> Bootstrap 5 / Tabler compatibility shim
   --------------------------------------------------------------------------
   Load order (Tabler pages only):
     js/jquery-3.7.0.min.js
     js/vendor/tabler/tabler.min.js
     js/gems-bs5-jquery.js        <- this file
     DataTables, toastr, js/common.js, page scripts

   WHY THIS FILE EXISTS
   Tabler 1.5 bundles Bootstrap 5.3.8 but strips Bootstrap's jQuery interop:
   there is no jQueryInterface registration and component events are dispatched
   as plain native events (new Event(name, {bubbles:true, cancelable:true})).
   GEMS has 200+ $('#modal').modal('show'|'hide') calls, ~45 jQuery
   .on('hidden.bs.modal') listeners and 200+ legacy data-dismiss/data-toggle
   attributes. Without this shim all of that silently dies.

   THREE JOBS
     1. Register $.fn.modal / tooltip / popover / collapse / tab / dropdown /
        toast / offcanvas / alert with Bootstrap 4 call semantics.
     2. Bridge native "*.bs.*" events into jQuery so .on('hidden.bs.modal')
        keeps firing (and preventDefault() on cancelable events still works).
     3. Make legacy data-dismiss / data-toggle attributes work, since BS5 only
        listens to data-bs-*. data-dismiss is delegated here; behavioural
        data-toggle values are rewritten to data-bs-toggle so BS5 owns them
        (see the upgrade section for why emulation alone is not enough).

   EXPLICIT NON-GOALS — do not add these:
     MDB / Materialize behaviour. sideNav, materialSelect, collapsible,
     pickadate, pickatime, characterCounter, Materialize dropdown
     (data-activates), the .material-tooltip-main template, mdb-lightbox and
     enhanced-modals side/frame modals all stay on the MDB pages. common.js
     dual-init decides which path runs.
     $.fn.button is NOT defined here: in GEMS that name belongs to the
     DataTables Buttons API.
   ========================================================================== */
(function (window, $) {
  'use strict';

  if (!$) {
    window.console && console.error('[gems-bs5-shim] jQuery must load before this file.');
    return;
  }
  if ($.fn.gemsBs5Shim) { return; }      // never install twice

  var bs = window.bootstrap || (window.tabler && window.tabler.bootstrap) || window.tabler;
  if (!bs || !bs.Modal) {
    window.console && console.error('[gems-bs5-shim] Tabler/Bootstrap 5 bundle must load before this file.');
    return;
  }

  $.fn.gemsBs5Shim = '1.0';

  /* ----------------------------------------------------------------------
     Legacy data-* -> Bootstrap 5 config mapping
     Old markup carries data-placement="top", data-trigger, title, etc.
     BS5 reads data-bs-* only, so copy what the page already declares.
     ---------------------------------------------------------------------- */
  var LEGACY_OPTION_ATTRS = [
    // tooltip / popover
    'placement', 'trigger', 'html', 'container', 'content', 'template',
    'animation', 'offset', 'boundary', 'title', 'customClass',
    'fallbackPlacement', 'selector', 'sanitize',
    // modal / offcanvas — data-backdrop="static" data-keyboard="false" is real
    // markup in this repo (pages/home.html #modalExportProgress). Without these
    // the dialog silently becomes dismissible.
    'backdrop', 'keyboard', 'focus', 'scroll',
    // collapse
    'parent',
    // toast
    'autohide', 'delay'
  ];

  // BS5 type-checks its config, so "100" must reach it as a number, not a string.
  // 'offset' is deliberately NOT here: BS5 types it as (array|string|function), so
  // coercing a legal BS4 data-offset="10" to a number would make it throw. Left as
  // a string, BS5 parses it itself.
  var NUMERIC_OPTIONS = { delay: true };

  function coerce(key, raw) {
    if (raw === 'true') { return true; }
    if (raw === 'false') { return false; }
    if (NUMERIC_OPTIONS[key] && raw !== '' && !isNaN(raw)) { return Number(raw); }
    return raw;
  }

  function legacyConfig(el) {
    var cfg = {};
    for (var i = 0; i < LEGACY_OPTION_ATTRS.length; i++) {
      var key = LEGACY_OPTION_ATTRS[i];
      var attr = 'data-' + key.replace(/[A-Z]/g, function (c) { return '-' + c.toLowerCase(); });
      // Only take the legacy attribute; data-bs-* is read by Bootstrap itself.
      if (el.hasAttribute(attr) && !el.hasAttribute('data-bs-' + key)) {
        cfg[key] = coerce(key, el.getAttribute(attr));
      }
    }
    return cfg;
  }

  /* ----------------------------------------------------------------------
     $.fn.<component> with Bootstrap 4 semantics
     ---------------------------------------------------------------------- */
  // Bootstrap 4 showed the modal for $(el).modal() and $(el).modal({...});
  // Bootstrap 5 only instantiates. Keep the old behaviour for these.
  var SHOW_ON_CONFIG = { modal: true, offcanvas: true };

  // Options that change how a modal behaves. If a later call passes a
  // different value we must rebuild the instance, because BS5 ignores config
  // on an existing instance. GEMS cycles {backdrop:'static'} -> 'hide' -> 'show'.
  var REBUILD_KEYS = ['backdrop', 'keyboard', 'focus'];

  function needsRebuild(instance, cfg) {
    if (!instance || !instance._config || !cfg) { return false; }
    // Never rebuild a dialog that is on screen. BS5's dispose() does not undo
    // show(): it orphans the backdrop and leaves <body> at overflow:hidden, so the
    // page stays unscrollable after the next hide. Bootstrap 4 also ignored new
    // options on an already-shown modal, so declining here matches the old
    // behaviour. Reachable in real code now that backdrop/keyboard can come from
    // data-backdrop/data-keyboard as well as from an explicit object.
    if (instance._isShown) { return false; }
    for (var i = 0; i < REBUILD_KEYS.length; i++) {
      var k = REBUILD_KEYS[i];
      if (Object.prototype.hasOwnProperty.call(cfg, k) && instance._config[k] !== cfg[k]) {
        return true;
      }
    }
    return false;
  }

  // Components whose visible state lives in a JS instance: if no instance exists
  // the thing was never opened, so 'hide'/'close' is a genuine no-op — matching
  // what Bootstrap 4 effectively did.
  //
  // Collapse and Alert are NOT in this list. Their state lives in the MARKUP
  // (.collapse.show, .alert), so an instance-less 'hide'/'close' must still act.
  // Real call site: pages/trade_ratio_config.html runs $('.collapse').collapse('hide')
  // against panels rendered with class="collapse show" and no instance yet.
  var STATE_IN_INSTANCE = {
    modal: true, tooltip: true, popover: true,
    toast: true, offcanvas: true, dropdown: true
  };

  // Creating a Collapse with the default toggle:true would immediately toggle the
  // panel, so an explicit 'hide'/'show' has to opt out of that.
  var CREATE_CONFIG = { collapse: { toggle: false } };

  function definePlugin(name, Ctor) {
    if (!Ctor) { return; }

    $.fn[name] = function (option, extra) {
      var args = Array.prototype.slice.call(arguments, 1);

      return this.each(function () {
        var el = this;

        // String form: $(el).modal('hide'), $(el).tooltip('dispose'), ...
        if (typeof option === 'string') {
          var existing = Ctor.getInstance(el);
          if (!existing) {
            var isTeardown = option === 'hide' || option === 'dispose' || option === 'close';
            if (isTeardown && STATE_IN_INSTANCE[name]) { return; }
            if (option === 'dispose') { return; }   // nothing to tear down anywhere
            existing = Ctor.getOrCreateInstance(
              el, $.extend({}, legacyConfig(el), CREATE_CONFIG[name] || {})
            );
          }
          if (typeof existing[option] !== 'function') {
            throw new TypeError('[gems-bs5-shim] No method "' + option + '" on ' + name);
          }
          existing[option].apply(existing, args);
          return;
        }

        // Object / undefined form
        var cfg = $.extend({}, legacyConfig(el), typeof option === 'object' ? option : {});
        var instance = Ctor.getInstance(el);

        if (instance && needsRebuild(instance, cfg)) {
          instance.dispose();
          instance = null;
        }
        if (!instance) {
          instance = Ctor.getOrCreateInstance(el, cfg);
        }
        if (SHOW_ON_CONFIG[name] && typeof instance.show === 'function') {
          instance.show();
        }
      });
    };

    $.fn[name].Constructor = Ctor;
  }

  definePlugin('modal', bs.Modal);
  definePlugin('tooltip', bs.Tooltip);
  definePlugin('popover', bs.Popover);
  definePlugin('collapse', bs.Collapse);
  definePlugin('tab', bs.Tab);
  definePlugin('dropdown', bs.Dropdown);
  definePlugin('toast', bs.Toast);
  definePlugin('offcanvas', bs.Offcanvas);
  definePlugin('alert', bs.Alert);

  /* ----------------------------------------------------------------------
     Native -> jQuery event bridge
     jQuery parses 'hidden.bs.modal' as type "hidden" + namespaces bs/modal and
     listens for a native "hidden" event, so Tabler's native "hidden.bs.modal"
     never reaches it. Bridge on document in the CAPTURE phase: that runs while
     Bootstrap is still dispatching, so a handler calling preventDefault() on a
     cancelable event (show.bs.modal / hide.bs.modal) still aborts the action,
     exactly as it did under Bootstrap 4.
     ---------------------------------------------------------------------- */
  var BRIDGED_EVENTS = [
    'show.bs.modal', 'shown.bs.modal', 'hide.bs.modal', 'hidden.bs.modal', 'hidePrevented.bs.modal',
    'show.bs.tab', 'shown.bs.tab', 'hide.bs.tab', 'hidden.bs.tab',
    'show.bs.collapse', 'shown.bs.collapse', 'hide.bs.collapse', 'hidden.bs.collapse',
    'show.bs.dropdown', 'shown.bs.dropdown', 'hide.bs.dropdown', 'hidden.bs.dropdown',
    'show.bs.tooltip', 'shown.bs.tooltip', 'hide.bs.tooltip', 'hidden.bs.tooltip', 'inserted.bs.tooltip',
    'show.bs.popover', 'shown.bs.popover', 'hide.bs.popover', 'hidden.bs.popover', 'inserted.bs.popover',
    'show.bs.toast', 'shown.bs.toast', 'hide.bs.toast', 'hidden.bs.toast',
    'show.bs.offcanvas', 'shown.bs.offcanvas', 'hide.bs.offcanvas', 'hidden.bs.offcanvas',
    'close.bs.alert', 'closed.bs.alert'
  ];

  BRIDGED_EVENTS.forEach(function (name) {
    document.addEventListener(name, function (nativeEvent) {
      var target = nativeEvent.target;
      if (!target || target.nodeType !== 1) { return; }

      var jqEvent = $.Event(name, {
        relatedTarget: nativeEvent.relatedTarget,
        originalEvent: nativeEvent
      });
      // Triggering on the target lets the event bubble through jQuery, so both
      // $('#m').on(...) and $(document).on('...', '#m', ...) keep working.
      $(target).trigger(jqEvent);

      if (nativeEvent.cancelable && jqEvent.isDefaultPrevented()) {
        nativeEvent.preventDefault();
      }
    }, true);
  });

  /* ----------------------------------------------------------------------
     Legacy data-dismiss / data-toggle delegation
     BS5 only wires data-bs-*. Handlers are delegated on document so they also
     cover markup injected later by the .includeHtml $.load() pattern.
     ---------------------------------------------------------------------- */
  function resolveTarget(el) {
    var sel = el.getAttribute('data-target') || el.getAttribute('data-bs-target');
    if (!sel) {
      var href = el.getAttribute('href');
      if (href && href.charAt(0) === '#' && href.length > 1) { sel = href; }
    }
    if (!sel) { return null; }
    try { return document.querySelector(sel); } catch (e) { return null; }
  }

  /* ----------------------------------------------------------------------
     Legacy behavioural toggles: hand ownership to Bootstrap 5, ADDITIVELY.

     Emulating these by hand is not enough: BS5's outside-click (clearMenus) and
     keyboard handlers only ever look for [data-bs-toggle="dropdown"], so a
     shim-toggled dropdown never closes on an outside click or Escape. So the
     element must carry data-bs-toggle.

     We ADD data-bs-toggle and KEEP data-toggle, because page code selects on the
     legacy attribute — pages/ptw_form.html:3058 delegates
     $(document).on('shown.bs.tab', 'a[data-toggle="tab"]', ...) to resize its
     signature pad, and removing the attribute silently killed that handler.

     Keeping both is only safe because of the guard in the click handler below:
     BS5 and this shim would otherwise both act on one click and cancel out
     (measured: one click produced both a shown.bs.dropdown and a
     hidden.bs.dropdown, so the menu opened and instantly closed). The rule is
     therefore: if data-bs-toggle is present, Bootstrap owns the element and the
     shim does nothing.

     Only BEHAVIOURAL toggles are touched. tooltip/popover are left alone
     entirely; pages (and common.js:852) select them by data-toggle and
     initialise them explicitly.
     ---------------------------------------------------------------------- */
  var UPGRADE_KINDS = ['modal', 'collapse', 'tab', 'pill', 'list', 'dropdown'];
  var UPGRADE_SELECTOR = UPGRADE_KINDS.map(function (k) {
    return '[data-toggle="' + k + '"]';
  }).join(', ');

  function upgradeToggle(el) {
    var kind = el.getAttribute('data-toggle');
    if (!kind || UPGRADE_KINDS.indexOf(kind) === -1) { return kind; }
    if (!el.hasAttribute('data-bs-toggle')) { el.setAttribute('data-bs-toggle', kind); }
    // BS5 reads data-bs-target (or href); mirror the legacy target across.
    if (el.hasAttribute('data-target') && !el.hasAttribute('data-bs-target')) {
      el.setAttribute('data-bs-target', el.getAttribute('data-target'));
    }
    // data-toggle is deliberately RETAINED — see the note above.
    return kind;
  }

  function applyToggle(el, kind) {
    var target;
    if (kind === 'modal') {
      target = resolveTarget(el);
      // Pass the trigger as relatedTarget: Bootstrap 4 did, and page handlers
      // read event.relatedTarget inside show.bs.modal to decide what to load.
      if (target) { bs.Modal.getOrCreateInstance(target).show(el); }
    } else if (kind === 'collapse') {
      target = resolveTarget(el);
      if (target) { bs.Collapse.getOrCreateInstance(target, { toggle: false }).toggle(); }
    } else if (kind === 'dropdown') {
      bs.Dropdown.getOrCreateInstance(el).toggle();
    } else {
      // tab / pill / list. BS5 Tab requires the trigger to sit inside
      // .nav / .list-group / [role=tablist] and to be a .nav-link /
      // .list-group-item / [role=tab]; markup that does not satisfy that has to
      // be fixed in the page, not here.
      bs.Tab.getOrCreateInstance(el).show();
    }
  }

  window.gemsUpgradeLegacyToggles = function (root) {
    var scope = (root && root.querySelectorAll) ? root : document;
    var nodes = scope.querySelectorAll(UPGRADE_SELECTOR);
    for (var i = 0; i < nodes.length; i++) { upgradeToggle(nodes[i]); }
    return nodes.length;
  };

  $(document)
    .on('click.gemsBs5', '[data-dismiss="modal"]', function (event) {
      var host = this.closest('.modal');
      if (!host) { return; }
      event.preventDefault();
      var instance = bs.Modal.getInstance(host);
      if (instance) { instance.hide(); }
    })
    .on('click.gemsBs5', '[data-dismiss="alert"]', function (event) {
      var host = this.closest('.alert');
      if (!host) { return; }
      event.preventDefault();
      bs.Alert.getOrCreateInstance(host).close();
    })
    .on('click.gemsBs5', '[data-dismiss="toast"]', function (event) {
      var host = this.closest('.toast');
      if (!host) { return; }
      event.preventDefault();
      bs.Toast.getOrCreateInstance(host).hide();
    })
    .on('click.gemsBs5', UPGRADE_SELECTOR, function (event) {
      // THE GUARD that makes keeping both attributes safe: if data-bs-toggle is on
      // the element, Bootstrap's own data-api has already handled this click, and
      // acting again here would undo it.
      //
      // Why "already" is guaranteed, and it is NOT about registration order:
      // Bootstrap 5's EventHandler passes the delegation selector as the capture
      // argument (addEventListener(type, handler, selector) in the bundle), so every
      // delegated data-api handler runs in the CAPTURE phase at document. That
      // precedes any bubble-phase jQuery handler no matter who registered first — a
      // page may safely bind $(document).on('click', ...) above the framework block.
      // Bootstrap also resolves the delegated selector at dispatch time, which is
      // what makes the upgrade-then-act path below work.
      if (this.hasAttribute('data-bs-toggle')) { return; }

      // Otherwise this is markup injected since the last sweep — for example the
      // dropdowns js/pages/main_home.js builds as HTML strings. BS5's capture-phase
      // pass already ran and matched nothing, so nothing has happened yet: upgrade
      // the element, handle this one click, and let BS5 own every later one.
      //
      // Caveat (see the migrator checklist): jQuery does not dispatch a native click
      // for anchors, so $('a[data-toggle="tab"]').click() reaches neither Bootstrap
      // nor this handler. Call .tab('show') / .modal('show') on the target instead.
      var kind = upgradeToggle(this);
      event.preventDefault();
      applyToggle(this, kind);
    });

  // Sweep now (the shim may load after the DOM is parsed) and again on ready, so
  // the common case never depends on the click fallback above.
  window.gemsUpgradeLegacyToggles(document);
  $(function () { window.gemsUpgradeLegacyToggles(document); });

  /* ----------------------------------------------------------------------
     Convenience: initialise legacy tooltip/popover markup on a subtree and
     upgrade any behavioural toggles it brought with it. Call this after the
     .includeHtml $.load() pattern injects a fragment.
     ---------------------------------------------------------------------- */
  window.gemsInitTooltips = function (root) {
    var $root = root ? $(root) : $(document);
    window.gemsUpgradeLegacyToggles($root[0] || document);
    $root.find('[data-toggle="tooltip"], [data-bs-toggle="tooltip"]').tooltip();
    $root.find('[data-toggle="popover"], [data-bs-toggle="popover"]').popover();
  };
}(window, window.jQuery));
