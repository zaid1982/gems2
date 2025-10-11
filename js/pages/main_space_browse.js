'use strict';

(function(){
  const BOOKABLE_STATUSES = ['AVAILABLE', 'ACTIVE'];
  let allSpaces = [];
  let filteredSpaces = [];
  let isLoading = false;
  let userSiteId = 0;
  let isAdmin = false;

  function shouldLockSiteDropdown(){
    if (isAdmin) { return false; }
    if (!userSiteId) { return false; }
    return allSpaces.some(function(space){ return String(space.siteId) === String(userSiteId); });
  }

  function defaultSiteValue(){
    return shouldLockSiteDropdown() ? String(userSiteId) : 'all';
  }

  function setSelectState($select, options){
    if (!$select || !$select.length) { return; }
    const opts = options || {};
    if (opts.value !== undefined) {
      $select.val(String(opts.value));
    }
    if (typeof opts.disabled !== 'undefined') {
      $select.prop('disabled', !!opts.disabled);
    }
  }

  function headers(){
    const t = sessionStorage.getItem('token');
    return t ? { 'Authorization': 'Bearer ' + t } : {};
  }

  function resolveUserContext(){
    try {
      if (typeof mzIsRoleExist === 'function') {
        isAdmin = !!mzIsRoleExist('1,10');
      }
    } catch(e) { isAdmin = false; }
    try {
      if (typeof mzGetUserInfoByParam === 'function') {
        const site = mzGetUserInfoByParam('siteId');
        if (site) {
          const parsed = parseInt(site, 10);
          if (!isNaN(parsed)) { userSiteId = parsed; }
        }
      }
      if (!userSiteId) {
        let info = sessionStorage.getItem('userInfo');
        if (info) {
          info = JSON.parse(info);
          const site = info.siteId || info.siteID || info.site_id;
          if (site) {
            const parsed = parseInt(site, 10);
            if (!isNaN(parsed)) { userSiteId = parsed; }
          }
          if (!isAdmin && Array.isArray(info.roles)) {
            isAdmin = info.roles.some(function(role){ const rid = String(role.roleId || role); return rid === '1' || rid === '10'; });
          }
        }
      }
    } catch(e) { /* noop */ }
  }

  function normalizeSpace(space){
    const status = (space.spaceStatus || '').toUpperCase();
    const coverUrl = (space.coverPhotoUrl && space.coverPhotoUrl.length) ? space.coverPhotoUrl : 'img/background/no-image.png';
    return Object.assign({}, space, {
      normalizedStatus: status,
      coverPhotoUrl: coverUrl,
      spaceCapacity: space.spaceCapacity || 0,
      activeReservationCount: space.activeReservationCount || 0
    });
  }

  function fetchSpaces(){
    if (isLoading) { return; }
    isLoading = true;
    ShowLoader();
    $('#lblSpaceCount').text('Loading spaces…');
    $.ajax({
      url: 'api/space.php',
      method: 'GET',
      dataType: 'json',
      headers: headers()
    }).done(function(resp){
      if (resp && resp.success) {
        const list = Array.isArray(resp.result) ? resp.result : [];
        allSpaces = list.map(normalizeSpace);
        buildSiteOptions(allSpaces);
        buildCapacityOptions(allSpaces);
        applyFilters();
      } else {
        toastr['error']((resp && resp.errmsg) || 'Failed to load spaces');
        allSpaces = [];
        applyFilters();
      }
    }).fail(function(jq){
      const message = jq.responseJSON && jq.responseJSON.errmsg ? jq.responseJSON.errmsg : 'Failed to load spaces';
      toastr['error'](message);
      allSpaces = [];
      applyFilters();
    }).always(function(){
      isLoading = false;
      HideLoader();
    });
  }

  function buildSiteOptions(list){
    const select = $('#optSpaceSite');
    const previous = select.val() || 'all';
    const siteMap = new Map();
    list.forEach(function(space){
      const siteId = space.siteId ? String(space.siteId) : '';
      if (!siteId) { return; }
      if (!siteMap.has(siteId)) {
        siteMap.set(siteId, space.siteName || ('Site #' + siteId));
      }
    });
    const options = Array.from(siteMap.entries()).sort(function(a,b){
      return a[1].localeCompare(b[1]);
    });
    select.find('option:not([value="all"])').remove();
    options.forEach(function(entry){
      const opt = $('<option></option>').attr('value', entry[0]).text(entry[1]);
      select.append(opt);
    });
    const siteKey = String(userSiteId || '');
    const lockForSite = !isAdmin && !!siteKey && siteMap.has(siteKey);
    let desiredValue = 'all';
    if (lockForSite) {
      desiredValue = siteKey;
    } else if (previous && (previous === 'all' || siteMap.has(previous))) {
      desiredValue = previous;
    }
  setSelectState(select, { value: desiredValue, disabled: lockForSite });
  }

  function buildCapacityOptions(list){
    const select = $('#optSpaceCapacity');
    const previous = parseInt(select.val() || '0', 10) || 0;
    const caps = Array.from(new Set(list.map(function(space){ return parseInt(space.spaceCapacity || 0, 10) || 0; }))).filter(function(n){ return n > 0; }).sort(function(a,b){ return a-b; });
    select.find('option[data-dynamic="1"]').remove();
    const thresholds = [];
    caps.forEach(function(cap){
      if (!thresholds.length) {
        thresholds.push(cap);
        return;
      }
      const last = thresholds[thresholds.length-1];
      if (cap - last >= 5) {
        thresholds.push(cap);
      }
    });
    thresholds.forEach(function(cap){
      const opt = $('<option></option>').attr('value', String(cap)).attr('data-dynamic', '1').text(cap + '+ seats');
      select.append(opt);
    });
  const desired = (previous && thresholds.some(function(cap){ return cap === previous; })) ? String(previous) : '0';
  setSelectState(select, { value: desired, disabled: false });
  }

  function applyFilters(){
    const searchTerm = ($('#txtSpaceSearch').val() || '').trim().toLowerCase();
    const siteFilter = $('#optSpaceSite').val() || 'all';
    const capacityFilter = parseInt($('#optSpaceCapacity').val() || '0', 10) || 0;
    const onlyBookable = $('#chkSpaceAvailable').prop('checked');

    filteredSpaces = allSpaces.filter(function(space){
      if (searchTerm) {
        const haystack = [
          space.spaceName,
          space.locationName,
          space.categoryName,
          space.typeName,
          space.siteName
        ].map(function(val){ return (val || '').toString().toLowerCase(); }).join(' ');
        if (haystack.indexOf(searchTerm) === -1) { return false; }
      }

      if (siteFilter !== 'all' && String(space.siteId) !== siteFilter) { return false; }

      const capacity = parseInt(space.spaceCapacity || 0, 10) || 0;
      if (capacityFilter > 0 && capacity < capacityFilter) { return false; }

      if (onlyBookable) {
        if (BOOKABLE_STATUSES.indexOf(space.normalizedStatus) === -1) { return false; }
      }

      return true;
    });

    renderSpaces(filteredSpaces);
    updateCount(filteredSpaces.length, siteFilter, capacityFilter, onlyBookable);
  }

  function formatSiteLabel(siteFilter){
    if (siteFilter === 'all' || !siteFilter) { return 'All sites'; }
    const match = allSpaces.find(function(space){ return String(space.siteId) === siteFilter; });
    return match && match.siteName ? match.siteName : ('Site ' + siteFilter);
  }

  function updateCount(count, siteFilter, capacityFilter, onlyBookable){
    if (isLoading) { return; }
    const pieces = [];
    if (count === 0) {
      pieces.push('No spaces found');
    } else if (count === 1) {
      pieces.push('1 space found');
    } else {
      pieces.push(count + ' spaces found');
    }
    pieces.push('Site: ' + formatSiteLabel(siteFilter));
    if (capacityFilter > 0) {
      pieces.push('Min capacity: ' + capacityFilter + '+');
    } else {
      pieces.push('Min capacity: Any');
    }
    pieces.push(onlyBookable ? 'Bookable only' : 'All statuses');
    $('#lblSpaceCount').text(pieces.join(' • '));
  }

  function statusBadgeMeta(status){
    switch (status) {
      case 'AVAILABLE':
        return { label: 'Available', cls: 'space-card__badge--available' };
      case 'ACTIVE':
        return { label: 'Active', cls: 'space-card__badge--available' };
      case 'RESERVED':
        return { label: 'Reserved', cls: 'space-card__badge--reserved' };
      case 'DISABLED':
      case 'INACTIVE':
      case 'DECOMMISSIONED':
        return { label: 'Unavailable', cls: 'space-card__badge--disabled' };
      default:
        return { label: status || 'Status', cls: '' };
    }
  }

  function renderSpaces(list){
    const $grid = $('#spaceGrid');
    const $empty = $('#spaceGridEmpty');
    $grid.empty();

    if (!list.length) {
      $empty.removeClass('d-none');
      return;
    }

    $empty.addClass('d-none');

    list.forEach(function(space){
      const $col = $('<div class="col-12 col-md-6 col-xl-4 mb-4"></div>');
      const $card = $('<div class="space-card"></div>');

      const $media = $('<div class="space-card__media"></div>');
      const $img = $('<img>', {
        src: space.coverPhotoUrl || 'img/background/no-image.png',
        alt: space.spaceName ? space.spaceName : 'Space photo'
      });
      $img.on('error', function(){
        const $el = $(this);
        if ($el.data('fallback-applied')) { return; }
        $el.data('fallback-applied', true);
        $el.attr('src', 'img/background/no-image.png');
      });
      $media.append($img);

      const badgeMeta = statusBadgeMeta(space.normalizedStatus);
      const $badge = $('<div class="space-card__badge"></div>').addClass(badgeMeta.cls).text(badgeMeta.label);
      $media.append($badge);

      const $body = $('<div class="space-card__body"></div>');
      const $title = $('<div class="space-card__title"></div>');
      $title.append($('<span></span>').text(space.spaceName || 'Untitled space'));
      if (space.siteName) {
        const $sitePill = $('<span class="badge badge-light text-uppercase"></span>').text(space.siteName);
        $title.append($sitePill);
      }
      $body.append($title);

      if (space.locationName) {
        const $subtitle = $('<div class="space-card__subtitle"></div>').text(space.locationName);
        $body.append($subtitle);
      }

      const $metaCapacity = $('<div class="space-card__meta"></div>');
      $metaCapacity.append('<i class="fas fa-users"></i>');
      $metaCapacity.append($('<span></span>').text((space.spaceCapacity ? space.spaceCapacity + ' pax' : 'Capacity TBD')));
      $body.append($metaCapacity);

      if (space.spaceArea) {
        const $metaArea = $('<div class="space-card__meta"></div>');
        $metaArea.append('<i class="fas fa-expand-arrows-alt"></i>');
        $metaArea.append($('<span></span>').text(space.spaceArea + ' sqft'));
        $body.append($metaArea);
      }

      if (space.typeName || space.categoryName) {
        const $metaType = $('<div class="space-card__meta"></div>');
        $metaType.append('<i class="fas fa-layer-group"></i>');
        const typePieces = [];
        if (space.categoryName) { typePieces.push(space.categoryName); }
        if (space.typeName) { typePieces.push(space.typeName); }
        $metaType.append($('<span></span>').text(typePieces.join(' • ')));
        $body.append($metaType);
      }

      const $stats = $('<div class="space-card__stats"></div>');
      $stats.append($('<div><i class="far fa-calendar-check mr-1"></i>' + space.activeReservationCount + ' upcoming reservations</div>'));
      $body.append($stats);

      const $actions = $('<div class="space-card__actions"></div>');
      const previewUrl = 'space_preview.html?id=' + encodeURIComponent(space.spaceId);
      const calendarUrl = 'space_calendar.html?id=' + encodeURIComponent(space.spaceId);
      const $previewBtn = $('<a class="btn btn-outline-primary btn-sm"><i class="far fa-eye mr-1"></i>View details</a>').attr('href', previewUrl);
      const $calendarBtn = $('<a class="btn btn-primary btn-sm"><i class="far fa-calendar-alt mr-1"></i>Check availability</a>').attr('href', calendarUrl);
      $actions.append($previewBtn, $calendarBtn);

      $card.append($media, $body, $actions);
      $col.append($card);
      $grid.append($col);
    });
  }

  function resetFilters(){
    $('#txtSpaceSearch').val('');
    const lockForSite = shouldLockSiteDropdown();
    const siteVal = lockForSite ? String(userSiteId) : 'all';
    $('#optSpaceSite').val(siteVal);
    $('#optSpaceCapacity').val('0');
    $('#chkSpaceAvailable').prop('checked', true);
    setSelectState($('#optSpaceSite'), { value: siteVal, disabled: lockForSite });
    setSelectState($('#optSpaceCapacity'), { value: '0', disabled: false });
    applyFilters();
  }

  function wireEvents(){
    $('#txtSpaceSearch').on('input', debounce(applyFilters, 200));
    $('#optSpaceSite, #optSpaceCapacity').on('change', applyFilters);
    $('#chkSpaceAvailable').on('change', applyFilters);
    $('#btnSpaceReset').on('click', resetFilters);
  }

  function debounce(fn, wait){
    let timeout = null;
    return function(){
      const context = this;
      const args = arguments;
      clearTimeout(timeout);
      timeout = setTimeout(function(){ fn.apply(context, args); }, wait);
    };
  }

  function boot(){
    resolveUserContext();
    try { if (typeof initiatePages === 'function') { initiatePages(); } }
    catch (e) { console && console.warn && console.warn('initiatePages error', e); }
    setSelectState($('#optSpaceSite'), { value: defaultSiteValue(), disabled: shouldLockSiteDropdown() });
    setSelectState($('#optSpaceCapacity'), { value: '0', disabled: false });
    wireEvents();
    fetchSpaces();
  }

  $(document).ready(function(){
    let pending = $('.includeHtml').length;
    if (pending === 0) {
      boot();
    } else {
      $('.includeHtml').each(function(){
        const id = $(this).attr('id');
        $('#' + id).load('html/' + id.substr(2) + '.html?' + (new Date().valueOf()), function(){
          pending--;
          if (pending === 0) { boot(); }
        });
      });
    }
  });
})();
