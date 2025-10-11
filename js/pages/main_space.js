(function() {
    'use strict';

    let dt;
    let currentSiteId = '';
    let cachedSpaces = [];
    let currentView = 'list';
    let isAdmin = false;

    function fetchSpaces(params) {
        const token = sessionStorage.getItem('token');
        const headers = token ? { 'Authorization': 'Bearer ' + token } : {};
        return $.ajax({ url: 'api/space.php' + buildQuery(params), method: 'GET', dataType: 'json', headers });
    }
    function buildQuery(params) { const q = []; if (params.siteId) q.push('siteId=' + encodeURIComponent(params.siteId)); if (params.status) q.push('status=' + encodeURIComponent(params.status)); return q.length ? ('?' + q.join('&')) : ''; }
    function statusBadge(status) { const map = { AVAILABLE: 'success', RESERVED: 'warning', DISABLED: 'danger', ACTIVE: 'info' }; const cls = map[status] || 'dark'; return '<span class="badge-modern badge-' + cls + '">' + status + '</span>'; }

    function initDataTable() {
        isAdmin = !!mzIsRoleExist('1,10');
    dt = $('#dtSpc').DataTable({ language: _DATATABLE_LANGUAGE, searching: true, ordering: true, responsive: true, paging: true, dom:'Bfrtip', buttons:[{extend:'csv', title:'spaces'},{extend:'excel', title:'spaces'},{extend:'print', title:'spaces'}], columnDefs: [{ targets: 0, orderable: false, searchable: false }, { targets: -1, orderable: false, searchable: false }], order: [[1, 'asc']], data: [], columns: [ { data: null, render: function() { return ''; } }, { data: 'spaceName' }, { data: 'locationName', defaultContent: '' }, { data: 'categoryName', defaultContent: '' }, { data: 'typeName', defaultContent: '' }, { data: 'spaceArea', defaultContent: '' }, { data: 'spaceCapacity', defaultContent: '' }, { data: 'spaceStatus', render: function(d){ return statusBadge(d); } }, { data: null, render: function(row){ 
            let html = '<div class="btn-group btn-group-sm" role="group">';
            if (isAdmin) {
                html += '<button type="button" class="btn btn-outline-secondary btnSpcManage" title="Manage"><i class="fas fa-tools"></i></button>';
                html += '<button type="button" class="btn btn-outline-danger btnSpcDelete" title="Delete"><i class="fas fa-trash"></i></button>';
            }
            html += '<button type="button" class="btn btn-outline-primary btnSpcView" title="View"><i class="fa fa-eye"></i></button>';
            html += '</div>'; return html; 
        } } ] });
    dt.on('order.dt search.dt', function () { dt.column(0, { search: 'applied', order: 'applied' }).nodes().each(function (cell, i) { cell.innerHTML = i + 1; }); }).draw();
    dt.on('draw', function(){ if (currentView === 'card') { renderCardView(getFilteredSpaces()); } });
    const $btns=$(dt.buttons().container()); $('#dtSpcButtons').append($btns).addClass('d-none');
    $('#spcExportToggle .segmented-btn').on('click', function(){ const type=$(this).data('export'); if(type==='csv'){ dt.button(0).trigger(); } else if(type==='excel'){ dt.button(1).trigger(); } else if(type==='print'){ dt.button(2).trigger(); } });
    }

    function loadSpaces(options) {
        if (options && typeof options.preventDefault === 'function') {
            options.preventDefault();
            options = {};
        }
        options = options || {};
        const skipLoader = options.skipLoader === true;
        console.log('Loading spaces for site:', currentSiteId, 'status:', $('#optSpcStatus').val());
        if (!skipLoader) { ShowLoader(); }
        const status = $('#optSpcStatus').val();
        const params = { siteId: currentSiteId, status: status };
        return fetchSpaces(params)
            .done(function(resp){
                if (resp && resp.success) {
                    cachedSpaces = Array.isArray(resp.result) ? resp.result : [];
                    dt.clear().rows.add(cachedSpaces).draw();
                    if (currentView === 'card') {
                        renderCardView(getFilteredSpaces());
                    }
                } else {
                    cachedSpaces = [];
                    try { console.error('Space list error:', resp); } catch(e) {}
                    alert((resp && resp.errmsg) || _ALERT_MSG_ERROR_DEFAULT);
                    if (currentView === 'card') {
                        renderCardView([]);
                    }
                }
            })
            .fail(function(jqXHR){
                cachedSpaces = [];
                try { console.error('Space list request failed', jqXHR.status, jqXHR.responseText); } catch(e) {}
                alert(_ALERT_MSG_ERROR_DEFAULT);
                if (currentView === 'card') {
                    renderCardView([]);
                }
            })
            .always(function(){ if (!skipLoader) { HideLoader(); } });
    }

    function normalizeCard(space) {
        const obj = $.extend({}, space);
        obj.spaceStatus = (obj.spaceStatus || '').toUpperCase();
        obj.coverPhotoUrl = obj.coverPhotoUrl || 'img/background/no-image.png';
        const capacityVal = parseInt(obj.spaceCapacity, 10);
        obj.spaceCapacity = isNaN(capacityVal) ? null : capacityVal;
        obj.spaceArea = obj.spaceArea || '';
        const resvVal = parseInt(obj.activeReservationCount, 10);
        obj.activeReservationCount = isNaN(resvVal) ? 0 : resvVal;
        return obj;
    }

    function statusBadgeMeta(status) {
        switch (status) {
            case 'AVAILABLE':
            case 'ACTIVE':
                return { label: 'Available', cls: 'space-card__badge--available' };
            case 'RESERVED':
                return { label: 'Reserved', cls: 'space-card__badge--reserved' };
            case 'DISABLED':
            case 'INACTIVE':
                return { label: 'Unavailable', cls: 'space-card__badge--disabled' };
            default:
                return { label: status || 'Status', cls: '' };
        }
    }

    function renderCardView(list) {
        const $grid = $('#spcCardGrid');
        const $empty = $('#spcCardEmpty');
        $grid.empty();
        if (!Array.isArray(list) || list.length === 0) {
            $empty.removeClass('d-none');
            return;
        }
        $empty.addClass('d-none');
        list.map(normalizeCard).forEach(function(space){
            const $col = $('<div class="space-card-grid__col"></div>');
            const $card = $('<div class="space-card"></div>');
            const $media = $('<div class="space-card__media"></div>');
            const $img = $('<img>', { src: space.coverPhotoUrl, alt: space.spaceName || 'Space photo' });
            $img.on('error', function(){ $(this).attr('src', 'img/background/no-image.png'); });
            const meta = statusBadgeMeta(space.spaceStatus);
            const $badge = $('<div class="space-card__badge"></div>').addClass(meta.cls).text(meta.label);
            $media.append($img, $badge);

            const $body = $('<div class="space-card__body"></div>');
            const $title = $('<div class="space-card__title"></div>');
            $title.append($('<span></span>').text(space.spaceName || 'Unnamed space'));
            if (space.siteName) {
                $title.append($('<span class="badge badge-light text-uppercase"></span>').text(space.siteName));
            }
            $body.append($title);
            if (space.locationName) {
                const $loc = $('<div class="space-card__subtitle"></div>');
                $loc.append('<i class="fas fa-map-marker-alt"></i>');
                $loc.append($('<span></span>').text(space.locationName));
                $body.append($loc);
            }
            const capacityText = space.spaceCapacity !== null ? (space.spaceCapacity + ' pax') : 'Capacity TBD';
            const $cap = $('<div class="space-card__meta"></div>');
            $cap.append('<i class="fas fa-users"></i>');
            $cap.append($('<span></span>').text(capacityText));
            $body.append($cap);
            if (space.spaceArea) {
                const $area = $('<div class="space-card__meta"></div>');
                $area.append('<i class="fas fa-expand-arrows-alt"></i>');
                $area.append($('<span></span>').text(space.spaceArea + ' sqft'));
                $body.append($area);
            }
            if (space.categoryName || space.typeName) {
                const $catType = $('<div class="space-card__meta"></div>');
                const pieces = [];
                if (space.categoryName) { pieces.push(space.categoryName); }
                if (space.typeName) { pieces.push(space.typeName); }
                $catType.append('<i class="fas fa-layer-group"></i>');
                $catType.append($('<span></span>').text(pieces.join(' • ')));
                $body.append($catType);
            }
            const $stat = $('<div class="space-card__stats"></div>');
            $stat.append('<div><i class="far fa-calendar-check mr-1"></i>' + space.activeReservationCount + ' upcoming reservations</div>');
            $body.append($stat);

            const $actions = $('<div class="space-card__actions"></div>');
            const previewUrl = 'space_preview.html?id=' + encodeURIComponent(space.spaceId);
            const calendarUrl = 'space_calendar.html?id=' + encodeURIComponent(space.spaceId);
            const $previewBtn = $('<a class="btn btn-outline-primary btn-sm"><i class="far fa-eye mr-1"></i>View</a>').attr('href', previewUrl);
            const $calendarBtn = $('<a class="btn btn-primary btn-sm"><i class="far fa-calendar-alt mr-1"></i>Calendar</a>').attr('href', calendarUrl);
            $actions.append($previewBtn, $calendarBtn);
            if (isAdmin) {
                const $manageBtn = $('<button type="button" class="btn btn-secondary btn-sm btnSpcCardManage"><i class="fas fa-tools mr-1"></i>Manage</button>').attr('data-id', space.spaceId);
                const $deleteBtn = $('<button type="button" class="btn btn-outline-danger btn-sm btnSpcCardDelete"><i class="fas fa-trash mr-1"></i>Delete</button>').attr('data-id', space.spaceId);
                $actions.append($manageBtn, $deleteBtn);
            }

            $card.append($media, $body, $actions);
            $col.append($card);
            $grid.append($col);
        });
    }

    function getFilteredSpaces() {
        if (!dt) { return cachedSpaces.slice(); }
        const dataApi = dt.rows({ search: 'applied' });
        const arr = [];
        dataApi.every(function(){ arr.push(this.data()); return true; });
        return arr;
    }

    function findSpaceById(id) {
        if (!id) { return null; }
        const numeric = parseInt(id, 10);
        for (let i = 0; i < cachedSpaces.length; i++) {
            if (parseInt(cachedSpaces[i].spaceId, 10) === numeric) {
                return cachedSpaces[i];
            }
        }
        return null;
    }

    function setView(view) {
        currentView = view;
        if (view === 'card') {
            $('#spcTableWrapper').addClass('d-none');
            $('#spcCardWrapper').removeClass('d-none');
            $('#spcExportToggle').addClass('d-none');
            $('#btnSpcViewCard').addClass('is-active');
            $('#btnSpcViewList').removeClass('is-active');
            renderCardView(getFilteredSpaces());
        } else {
            $('#spcCardWrapper').addClass('d-none');
            $('#spcTableWrapper').removeClass('d-none');
            $('#spcExportToggle').removeClass('d-none');
            $('#btnSpcViewList').addClass('is-active');
            $('#btnSpcViewCard').removeClass('is-active');
            if (dt && typeof dt.columns === 'function') {
                try { dt.columns.adjust(); } catch(e) { /* ignore */ }
                if (dt.responsive && typeof dt.responsive.recalc === 'function') {
                    try { dt.responsive.recalc(); } catch(e) { /* ignore */ }
                }
            }
        }
    }

    function initFilters() {
        const userSite = mzGetUserInfoByParam('siteId'); currentSiteId = userSite || ''; const isAdmin = mzIsRoleExist('1,10');
        if (!isAdmin && userSite) { $('#optSpcSiteId').append('<option value="' + userSite + '" selected>' + mzGetUserInfoByParam('siteName') + '</option>'); $('#optSpcSiteId').prop('disabled', true); } else { $('#optSpcSiteId').append('<option value="">All Sites</option>'); }
    // Destroy existing instances to avoid duplicate dropdowns created by global init
    try { $('#optSpcStatus').materialSelect('destroy'); } catch(e){}
    try { $('#optSpcColumns').materialSelect('destroy'); } catch(e){}
    try { $('#optSpcSiteId').materialSelect('destroy'); } catch(e){}
    // Also destroy if already wrapped (defensive)
    if ($('#optSpcStatus').parent().hasClass('select-wrapper')) { try { $('#optSpcStatus').materialSelect('destroy'); } catch(e){} }
    if ($('#optSpcColumns').parent().hasClass('select-wrapper')) { try { $('#optSpcColumns').materialSelect('destroy'); } catch(e){} }
    if ($('#optSpcSiteId').parent().hasClass('select-wrapper')) { try { $('#optSpcSiteId').materialSelect('destroy'); } catch(e){} }
        // Re-initialize after updating options/disabled state
    $('#optSpcStatus').materialSelect();
    $('#optSpcColumns').materialSelect();
    $('#optSpcSiteId').materialSelect();
        $('#optSpcStatus').on('change', loadSpaces); $('#optSpcSiteId').on('change', function(){ currentSiteId = $(this).val(); loadSpaces(); }); $('#txtSpcSearch').on('keyup', function(){ dt.search(this.value).draw(); });
        // For non-admins, default status to AVAILABLE to show bookable spaces first
        if (!isAdmin) {
            try { $('#optSpcStatus').val('AVAILABLE'); $('#optSpcStatus').materialSelect('destroy'); $('#optSpcStatus').materialSelect(); } catch(e){}
            // Hide Add Space button
            $('#btnSpcAdd').closest('.btn').hide();
        }
    }

    function loadRefs(categoryId) {
        function one(key) {
            const token = sessionStorage.getItem('token');
            const headers = token ? { 'Authorization': 'Bearer ' + token } : {};
            let url = 'api/space.php/refs/' + key;
            if (key === 'type' && categoryId) {
                url += ('?spaceCategoryId=' + encodeURIComponent(categoryId));
            }
            return $.ajax({ url, method: 'GET', dataType: 'json', headers });
        }
        return $.when(one('location'), one('category'), one('type'))
            .done(function(loc, cat, typ){
                const $loc = $('#optSpcLocation'), $cat = $('#optSpcCategory'), $typ = $('#optSpcType');
                // destroy previous instances to avoid MDB caching
                try { $loc.materialSelect('destroy'); } catch(e){}
                try { $cat.materialSelect('destroy'); } catch(e){}
                try { $typ.materialSelect('destroy'); } catch(e){}

                $loc.empty().append('<option value="" selected>--</option>');
                $cat.empty().append('<option value="" selected>--</option>');
                $typ.empty().append('<option value="" selected>--</option>');
                const locList = (loc[0].result||[]);
                locList.forEach(function(r){ $loc.append('<option value="'+r.spaceLocationId+'">'+r.spaceLocationName+'</option>'); });
                (cat[0].result||[]).forEach(function(r){ $cat.append('<option value="'+r.spaceCategoryId+'">'+r.spaceCategoryName+'</option>'); });
                (typ[0].result||[]).forEach(function(r){ $typ.append('<option value="'+r.spaceTypeId+'">'+r.spaceTypeName+'</option>'); });
                $loc.materialSelect(); $cat.materialSelect(); $typ.materialSelect();
                if (locList.length === 0) {
                    try { toastr['info']('No locations configured yet. Add locations in reference data to enable selection.', 'Info'); } catch(e) { /* no-op */ }
                }
                // when category changes inside modal, reload types filtered
                $cat.off('change._typeCascade').on('change._typeCascade', function(){
                    const cid = $(this).val();
                    const token = sessionStorage.getItem('token');
                    const headers = token ? { 'Authorization': 'Bearer ' + token } : {};
                    const url = 'api/space.php/refs/type' + (cid ? ('?spaceCategoryId='+encodeURIComponent(cid)) : '');
                    // destroy and rebuild type select
                    try { $typ.materialSelect('destroy'); } catch(e){}
                    $.ajax({ url, method: 'GET', dataType: 'json', headers})
                        .done(function(resp){
                            $typ.empty().append('<option value="" selected>--</option>');
                            (resp.result||[]).forEach(function(r){ $typ.append('<option value="'+r.spaceTypeId+'">'+r.spaceTypeName+'</option>'); });
                            $typ.materialSelect();
                        })
                        .fail(function(jqXHR){
                            try { console.error('Type refs load failed', jqXHR.status, jqXHR.responseText); } catch(e){}
                            $typ.empty().append('<option value="" selected>--</option>');
                            $typ.materialSelect();
                        });
                });
            })
            .fail(function(jqXHR){
                try { console.error('Refs load failed', jqXHR.status, jqXHR.responseText); } catch(e){}
                toastr['error']('Failed to load reference data. Please refresh.', _ALERT_TITLE_ERROR);
            });
    }

    function openCreateModal() {
        $('#frmSpc')[0].reset();
        // Ensure single instance for modal select
        try { $('#optSpcStatusForm').materialSelect('destroy'); } catch(e){}
        $('#optSpcStatusForm').materialSelect();
        const currentCat = $('#optSpcCategory').val();
        loadRefs(currentCat).always(function(){ $('#modalSpcForm').modal('show'); });
    }

    function buildCreatePayload() {
        const isAdmin = mzIsRoleExist('1,10');
        const siteId = isAdmin ? ($('#optSpcSiteId').val() || mzGetUserInfoByParam('siteId')) : mzGetUserInfoByParam('siteId');
        return {
            spaceName: $('#txtSpcName').val().trim(),
            siteId: parseInt(siteId || 0),
            status: $('#optSpcStatusForm').val(),
            locationId: mzParseInt($('#optSpcLocation').val()),
            categoryId: mzParseInt($('#optSpcCategory').val()),
            typeId: mzParseInt($('#optSpcType').val()),
            area: $('#txtSpcArea').val() || null,
            capacity: $('#txtSpcCapacity').val() || null,
            description: $('#txtSpcDesc').val() || null,
            assetIds: []
        };
    }

    function saveSpace() {
        const p = buildCreatePayload();
        if (!p.spaceName || !p.siteId || !p.status) { alert(_ALERT_MSG_VALIDATION); return; }
        ShowLoader();
    const token = sessionStorage.getItem('token');
    const headers = token ? { 'Authorization': 'Bearer ' + token } : {};
    $.ajax({ url: 'api/space.php', method: 'POST', data: JSON.stringify(p), contentType: 'application/json', dataType: 'json', headers })
    .done(function(resp){ if (resp && resp.success) { $('#modalSpcForm').modal('hide'); loadSpaces(); if (typeof mzToastSuccess==='function') mzToastSuccess(resp.errmsg||'Created'); else alert('Created'); } else { try { console.error('Space create error:', resp); } catch(e) {} alert((resp && resp.errmsg)||_ALERT_MSG_ERROR_DEFAULT); } })
    .fail(function(jqXHR){ try { console.error('Space create request failed', jqXHR.status, jqXHR.responseText); } catch(e) {} alert(_ALERT_MSG_ERROR_DEFAULT); })
        .always(function(){ HideLoader(); });
    }

    function deleteSpace(row, $triggerBtn) {
        if (!row || !row.spaceId) { return; }
        const spaceId = parseInt(row.spaceId);
        if (!spaceId) { return; }
        const name = row.spaceName ? ('"' + row.spaceName + '"') : 'this space';
        const confirmMsg = 'Delete ' + name + '? This will disable the space and archive it from listings.';
        if (!confirm(confirmMsg)) { return; }

        const token = sessionStorage.getItem('token');
        const headers = token ? { 'Authorization': 'Bearer ' + token } : {};
        if ($triggerBtn) { $triggerBtn.prop('disabled', true); }
        ShowLoader();
        $.ajax({ url: 'api/space.php/' + spaceId, method: 'DELETE', dataType: 'json', headers })
            .done(function(resp){
                if (resp && resp.success) {
                    if (typeof mzToastSuccess === 'function') {
                        mzToastSuccess(resp.errmsg || 'Space deleted');
                    } else if (typeof toastr !== 'undefined') {
                        toastr['success'](resp.errmsg || 'Space deleted', _ALERT_TITLE_SUCCESS);
                    } else {
                        alert(resp.errmsg || 'Space deleted');
                    }
                    loadSpaces({ skipLoader: true }).always(function(){ HideLoader(); });
                } else {
                    const msg = (resp && resp.errmsg) || _ALERT_MSG_ERROR_DEFAULT;
                    if (typeof toastr !== 'undefined') { toastr['error'](msg, _ALERT_TITLE_ERROR); } else { alert(msg); }
                    HideLoader();
                }
            })
            .fail(function(jqXHR){
                try { console.error('Space delete request failed', jqXHR.status, jqXHR.responseText); } catch(e) {}
                if (typeof toastr !== 'undefined') { toastr['error'](_ALERT_MSG_ERROR_DEFAULT, _ALERT_TITLE_ERROR); } else { alert(_ALERT_MSG_ERROR_DEFAULT); }
                HideLoader();
            })
            .always(function(){ if ($triggerBtn) { $triggerBtn.prop('disabled', false); } });
    }

    function initButtons() {
        $('#btnDtSpcRefresh').on('click', loadSpaces);
        $('#btnSpcAdd').on('click', openCreateModal);
        $('#btnSpcSave').on('click', saveSpace);

        $('#dtSpc').on('click', '.btnSpcView', function(){ const data = dt.row($(this).closest('tr')).data(); if (data && data.spaceId) { window.location.href = 'space_preview.html?id=' + data.spaceId; } });
        $('#dtSpc').on('click', '.btnSpcManage', function(){ const data = dt.row($(this).closest('tr')).data(); if (data && data.spaceId) { window.location.href = 'space_manage.html?id=' + data.spaceId; } });
        $('#dtSpc').on('click', '.btnSpcDelete', function(){ const data = dt.row($(this).closest('tr')).data(); if (data && data.spaceId) { deleteSpace(data, $(this)); } });

        $('#btnSpcViewList').on('click', function(){ setView('list'); });
        $('#btnSpcViewCard').on('click', function(){ setView('card'); });

        $('#spcCardGrid').on('click', '.btnSpcCardManage', function(){ const id = $(this).data('id'); const data = findSpaceById(id); if (data && data.spaceId) { window.location.href = 'space_manage.html?id=' + data.spaceId; } });
        $('#spcCardGrid').on('click', '.btnSpcCardDelete', function(){ const id = $(this).data('id'); const data = findSpaceById(id); if (data && data.spaceId) { deleteSpace(data, $(this)); } });
    }

    $(document).ready(function() {
        // Load shared html fragments from html/ directory, then initialize common layout
        let pending = $('.includeHtml').length;
        if (pending === 0) {
            try { if (typeof initiatePages === 'function') { initiatePages(); } } catch(e) { /* no-op */ }
            $('[data-toggle="popover"]').popover(); $('[data-toggle="tooltip"]').tooltip();
            initDataTable(); initFilters(); initButtons(); setView('list'); loadSpaces();
        } else {
            $('.includeHtml').each(function(){
                const id = $(this).attr('id');
                const target = 'html/' + id.substr(2) + '.html?' + new Date().valueOf();
                $('#'+id).load(target, function(){
                    pending--; if (pending === 0) {
                        try { if (typeof initiatePages === 'function') { initiatePages(); } } catch(e) { /* no-op */ }
                        $('[data-toggle="popover"]').popover(); $('[data-toggle="tooltip"]').tooltip();
                        initDataTable(); initFilters(); initButtons(); setView('list'); loadSpaces();
                    }
                });
            });
        }
    });
})();
