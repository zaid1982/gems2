(function () {
    'use strict';

    let dt;
    let currentSiteId = '';
    let cachedSpaces = [];
    let currentView = 'list';
    let isAdmin = false;
    let formValidate;

    function authHeaders() {
        const token = sessionStorage.getItem('token');
        return token ? { Authorization: 'Bearer ' + token } : {};
    }

    function fetchSpaces(params) {
        return $.ajax({ url: 'api/space.php' + buildQuery(params), method: 'GET', dataType: 'json', headers: authHeaders() });
    }

    function buildQuery(params) {
        const q = [];
        if (params.siteId) q.push('siteId=' + encodeURIComponent(params.siteId));
        if (params.status) q.push('status=' + encodeURIComponent(params.status));
        return q.length ? ('?' + q.join('&')) : '';
    }

    function statusBadge(status, type) {
        const label = status || '';
        if (type && type !== 'display') {
            return label;
        }
        const map = { AVAILABLE: 'success', RESERVED: 'warning', DISABLED: 'danger', ACTIVE: 'info' };
        return GemsUI.badge(map[status] || 'secondary', GemsUI.escape(label));
    }

    function initValidate() {
        formValidate = new MzValidate('frmSpc');
        formValidate.registerFields([
            { field_id: 'txtSpcName', type: 'text', name: 'Space Name', validator: { notEmpty: true, maxLength: 150 } },
            { field_id: 'optSpcStatusForm', type: 'select', name: 'Status', validator: { notEmpty: true } },
            { field_id: 'txtSpcDesc', type: 'text', name: 'Description', validator: { maxLength: 255 } }
        ]);
    }

    function initDataTable() {
        isAdmin = !!mzIsRoleExist('1,10');
        dt = $('#dtSpc').DataTable({
            language: GemsUI.dtEmpty('fa-door-open', 'No spaces recorded yet.', 'No spaces match the current search.'),
            searching: true,
            ordering: true,
            autoWidth: false,
            paging: true,
            dom: GemsUI.dtDomButtons,
            buttons: [
                { extend: 'csv', title: 'spaces', className: 'btn btn-outline-secondary btn-sm', text: '<i class="fas fa-file-csv"></i>', titleAttr: 'CSV' },
                { extend: 'excel', title: 'spaces', className: 'btn btn-outline-secondary btn-sm', text: '<i class="fas fa-file-excel"></i>', titleAttr: 'Excel' },
                { extend: 'print', title: 'spaces', className: 'btn btn-outline-secondary btn-sm', text: '<i class="fas fa-print"></i>', titleAttr: 'Print' }
            ],
            columnDefs: [
                { targets: 0, orderable: false, searchable: false },
                { targets: -1, orderable: false, searchable: false }
            ],
            order: [[1, 'asc']],
            data: [],
            columns: [
                { data: null, render: function () { return ''; } },
                { data: 'spaceName' },
                { data: 'locationName', defaultContent: '' },
                { data: 'categoryName', defaultContent: '' },
                { data: 'typeName', defaultContent: '' },
                { data: 'spaceArea', defaultContent: '' },
                { data: 'spaceCapacity', defaultContent: '' },
                {
                    data: 'spaceStatus',
                    render: function (d, type) { return statusBadge(d, type); }
                },
                {
                    data: null,
                    className: 'text-center text-nowrap noVis',
                    render: function (row) {
                        let html = '';
                        if (isAdmin) {
                            html += GemsUI.actionBtn({ tint: 'gems-btn-action-edit', cls: 'btnSpcManage', title: 'Manage', icon: 'fas fa-tools' });
                            html += GemsUI.actionBtn({ tint: 'gems-btn-action-delete', cls: 'btnSpcDelete', title: 'Delete', icon: 'fas fa-trash-alt' });
                        }
                        html += GemsUI.actionBtn({ tint: 'gems-btn-action-view', cls: 'btnSpcView', title: 'View', icon: 'fas fa-eye' });
                        return html;
                    }
                }
            ]
        });
        dt.on('order.dt search.dt', function () {
            dt.column(0, { search: 'applied', order: 'applied' }).nodes().each(function (cell, i) {
                cell.innerHTML = i + 1;
            });
        }).draw();
        dt.on('draw', function () {
            if (currentView === 'card') {
                renderCardView(getFilteredSpaces());
            }
        });
        dt.buttons().container().appendTo($('#dtSpcButtons'));
        GemsUI.bindDtTooltips('#dtSpc');
    }

    function loadSpaces(options) {
        if (options && typeof options.preventDefault === 'function') {
            options.preventDefault();
            options = {};
        }
        options = options || {};
        const skipLoader = options.skipLoader === true;
        if (!skipLoader) { ShowLoader(); }
        const status = $('#optSpcStatus').length ? $('#optSpcStatus').val() : '';
        const params = { siteId: currentSiteId, status: status };
        return fetchSpaces(params)
            .done(function (resp) {
                if (resp && resp.success) {
                    cachedSpaces = Array.isArray(resp.result) ? resp.result : [];
                    dt.clear().rows.add(cachedSpaces).draw();
                    if (currentView === 'card') {
                        renderCardView(getFilteredSpaces());
                    }
                } else {
                    cachedSpaces = [];
                    try { console.error('Space list error:', resp); } catch (e) {}
                    alert((resp && resp.errmsg) || _ALERT_MSG_ERROR_DEFAULT);
                    if (currentView === 'card') {
                        renderCardView([]);
                    }
                }
            })
            .fail(function (jqXHR) {
                cachedSpaces = [];
                try { console.error('Space list request failed', jqXHR.status, jqXHR.responseText); } catch (e) {}
                alert(_ALERT_MSG_ERROR_DEFAULT);
                if (currentView === 'card') {
                    renderCardView([]);
                }
            })
            .always(function () { if (!skipLoader) { HideLoader(); } });
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
        list.map(normalizeCard).forEach(function (space) {
            const $col = $('<div class="space-card-grid__col"></div>');
            const $card = $('<div class="space-card"></div>');
            const $media = $('<div class="space-card__media"></div>');
            const $img = $('<img>', { src: space.coverPhotoUrl, alt: space.spaceName || 'Space photo' });
            $img.on('error', function () { $(this).attr('src', 'img/background/no-image.png'); });
            const meta = statusBadgeMeta(space.spaceStatus);
            const $badge = $('<div class="space-card__badge"></div>').addClass(meta.cls).text(meta.label);
            $media.append($img, $badge);

            const $body = $('<div class="space-card__body"></div>');
            const $title = $('<div class="space-card__title"></div>');
            $title.append($('<span></span>').text(space.spaceName || 'Unnamed space'));
            if (space.siteName) {
                $title.append($('<span class="badge gems-badge gems-badge-secondary text-uppercase"></span>').text(space.siteName));
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
            $stat.append('<div><i class="far fa-calendar-check me-1"></i>' + space.activeReservationCount + ' upcoming reservations</div>');
            $body.append($stat);

            const $actions = $('<div class="space-card__actions"></div>');
            const previewUrl = 'space_preview.html?id=' + encodeURIComponent(space.spaceId);
            const calendarUrl = 'space_calendar.html?id=' + encodeURIComponent(space.spaceId);
            const $previewBtn = $('<a class="btn btn-outline-primary btn-sm"><i class="far fa-eye me-1"></i>View</a>').attr('href', previewUrl);
            const $calendarBtn = $('<a class="btn btn-primary btn-sm"><i class="far fa-calendar-alt me-1"></i>Calendar</a>').attr('href', calendarUrl);
            $actions.append($previewBtn, $calendarBtn);
            if (isAdmin) {
                const $manageBtn = $('<button type="button" class="btn btn-secondary btn-sm btnSpcCardManage"><i class="fas fa-tools me-1"></i>Manage</button>').attr('data-id', space.spaceId);
                const $deleteBtn = $('<button type="button" class="btn btn-outline-danger btn-sm btnSpcCardDelete"><i class="fas fa-trash me-1"></i>Delete</button>').attr('data-id', space.spaceId);
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
        dataApi.every(function () { arr.push(this.data()); return true; });
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
                try { dt.columns.adjust(); } catch (e) { /* ignore */ }
            }
        }
    }

    function initFilters() {
        const userSite = mzGetUserInfoByParam('siteId');
        currentSiteId = userSite || '';
        const adminUser = mzIsRoleExist('1,10');
        if ($('#optSpcSiteId').length) {
            if (!adminUser && userSite) {
                $('#optSpcSiteId').append('<option value="' + userSite + '" selected>' + mzGetUserInfoByParam('siteName') + '</option>');
                $('#optSpcSiteId').prop('disabled', true);
            } else {
                $('#optSpcSiteId').append('<option value="">All Sites</option>');
            }
            $('#optSpcSiteId').on('change', function () { currentSiteId = $(this).val(); loadSpaces(); });
        }
        if ($('#optSpcStatus').length) {
            $('#optSpcStatus').on('change', loadSpaces);
            if (!adminUser) {
                $('#optSpcStatus').val('AVAILABLE');
            }
        }
        $('#txtSpcSearch').on('keyup', function () { dt.search(this.value).draw(); });
        if (!adminUser) {
            $('#btnSpcAdd').closest('.btn').hide();
        }
    }

    function loadRefs(categoryId) {
        function one(key) {
            let url = 'api/space.php/refs/' + key;
            if (key === 'type' && categoryId) {
                url += ('?spaceCategoryId=' + encodeURIComponent(categoryId));
            }
            return $.ajax({ url: url, method: 'GET', dataType: 'json', headers: authHeaders() });
        }
        return $.when(one('location'), one('category'), one('type'))
            .done(function (loc, cat, typ) {
                GemsUI.fillSelect('optSpcLocation', loc[0].result || [], 'spaceLocationId', 'spaceLocationName', '--');
                GemsUI.fillSelect('optSpcCategory', cat[0].result || [], 'spaceCategoryId', 'spaceCategoryName', '--');
                GemsUI.fillSelect('optSpcType', typ[0].result || [], 'spaceTypeId', 'spaceTypeName', '--');
                if ((loc[0].result || []).length === 0) {
                    try { toastr['info']('No locations configured yet. Add locations in reference data to enable selection.', 'Info'); } catch (e) { /* no-op */ }
                }
                $('#optSpcCategory').off('change._typeCascade').on('change._typeCascade', function () {
                    const cid = $(this).val();
                    const url = 'api/space.php/refs/type' + (cid ? ('?spaceCategoryId=' + encodeURIComponent(cid)) : '');
                    $.ajax({ url: url, method: 'GET', dataType: 'json', headers: authHeaders() })
                        .done(function (resp) {
                            GemsUI.fillSelect('optSpcType', resp.result || [], 'spaceTypeId', 'spaceTypeName', '--');
                        })
                        .fail(function (jqXHR) {
                            try { console.error('Type refs load failed', jqXHR.status, jqXHR.responseText); } catch (e) {}
                            GemsUI.fillSelect('optSpcType', [], 'spaceTypeId', 'spaceTypeName', '--');
                        });
                });
            })
            .fail(function (jqXHR) {
                try { console.error('Refs load failed', jqXHR.status, jqXHR.responseText); } catch (e) {}
                toastr['error']('Failed to load reference data. Please refresh.', _ALERT_TITLE_ERROR);
            });
    }

    function openCreateModal() {
        if (formValidate) {
            formValidate.clearValidation();
        }
        $('#frmSpc')[0].reset();
        $('#optSpcStatusForm').val('AVAILABLE');
        const currentCat = $('#optSpcCategory').val();
        loadRefs(currentCat).always(function () { $('#modalSpcForm').modal('show'); });
    }

    function buildCreatePayload() {
        const adminUser = mzIsRoleExist('1,10');
        const siteId = adminUser ? ($('#optSpcSiteId').val() || mzGetUserInfoByParam('siteId')) : mzGetUserInfoByParam('siteId');
        return {
            spaceName: $('#txtSpcName').val().trim(),
            siteId: parseInt(siteId || 0, 10),
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
        if (formValidate && !formValidate.validateNow()) {
            toastr['error'](_ALERT_MSG_VALIDATION, _ALERT_TITLE_ERROR);
            return;
        }
        const p = buildCreatePayload();
        if (!p.spaceName || !p.siteId || !p.status) { alert(_ALERT_MSG_VALIDATION); return; }
        ShowLoader();
        $.ajax({ url: 'api/space.php', method: 'POST', data: JSON.stringify(p), contentType: 'application/json', dataType: 'json', headers: authHeaders() })
            .done(function (resp) {
                if (resp && resp.success) {
                    $('#modalSpcForm').modal('hide');
                    loadSpaces();
                    if (typeof mzToastSuccess === 'function') mzToastSuccess(resp.errmsg || 'Created');
                    else alert('Created');
                } else {
                    try { console.error('Space create error:', resp); } catch (e) {}
                    alert((resp && resp.errmsg) || _ALERT_MSG_ERROR_DEFAULT);
                }
            })
            .fail(function (jqXHR) {
                try { console.error('Space create request failed', jqXHR.status, jqXHR.responseText); } catch (e) {}
                alert(_ALERT_MSG_ERROR_DEFAULT);
            })
            .always(function () { HideLoader(); });
    }

    function deleteSpace(row, $triggerBtn) {
        if (!row || !row.spaceId) { return; }
        const spaceId = parseInt(row.spaceId, 10);
        if (!spaceId) { return; }
        const name = row.spaceName ? ('"' + row.spaceName + '"') : 'this space';
        const confirmMsg = 'Delete ' + name + '? This will disable the space and archive it from listings.';
        if (!confirm(confirmMsg)) { return; }

        if ($triggerBtn) { $triggerBtn.prop('disabled', true); }
        ShowLoader();
        $.ajax({ url: 'api/space.php/' + spaceId, method: 'DELETE', dataType: 'json', headers: authHeaders() })
            .done(function (resp) {
                if (resp && resp.success) {
                    if (typeof mzToastSuccess === 'function') {
                        mzToastSuccess(resp.errmsg || 'Space deleted');
                    } else if (typeof toastr !== 'undefined') {
                        toastr['success'](resp.errmsg || 'Space deleted', _ALERT_TITLE_SUCCESS);
                    } else {
                        alert(resp.errmsg || 'Space deleted');
                    }
                    loadSpaces({ skipLoader: true }).always(function () { HideLoader(); });
                } else {
                    const msg = (resp && resp.errmsg) || _ALERT_MSG_ERROR_DEFAULT;
                    if (typeof toastr !== 'undefined') { toastr['error'](msg, _ALERT_TITLE_ERROR); } else { alert(msg); }
                    HideLoader();
                }
            })
            .fail(function (jqXHR) {
                try { console.error('Space delete request failed', jqXHR.status, jqXHR.responseText); } catch (e) {}
                if (typeof toastr !== 'undefined') { toastr['error'](_ALERT_MSG_ERROR_DEFAULT, _ALERT_TITLE_ERROR); } else { alert(_ALERT_MSG_ERROR_DEFAULT); }
                HideLoader();
            })
            .always(function () { if ($triggerBtn) { $triggerBtn.prop('disabled', false); } });
    }

    function initButtons() {
        $('#btnDtSpcRefresh, #btnSpcRefreshTable').on('click', loadSpaces);
        $('#btnSpcAdd').on('click', openCreateModal);
        $('#btnSpcSave').on('click', saveSpace);

        $('#dtSpc').on('click', '.btnSpcView', function () {
            const data = dt.row($(this).closest('tr')).data();
            if (data && data.spaceId) { window.location.href = 'space_preview.html?id=' + data.spaceId; }
        });
        $('#dtSpc').on('click', '.btnSpcManage', function () {
            const data = dt.row($(this).closest('tr')).data();
            if (data && data.spaceId) { window.location.href = 'space_manage.html?id=' + data.spaceId; }
        });
        $('#dtSpc').on('click', '.btnSpcDelete', function () {
            const data = dt.row($(this).closest('tr')).data();
            if (data && data.spaceId) { deleteSpace(data, $(this)); }
        });

        $('#btnSpcViewList').on('click', function () { setView('list'); });
        $('#btnSpcViewCard').on('click', function () { setView('card'); });

        $('#spcCardGrid').on('click', '.btnSpcCardManage', function () {
            const id = $(this).data('id');
            const data = findSpaceById(id);
            if (data && data.spaceId) { window.location.href = 'space_manage.html?id=' + data.spaceId; }
        });
        $('#spcCardGrid').on('click', '.btnSpcCardDelete', function () {
            const id = $(this).data('id');
            const data = findSpaceById(id);
            if (data && data.spaceId) { deleteSpace(data, $(this)); }
        });
    }

    $(document).ready(function () {
        try { if (typeof initiatePages === 'function') { initiatePages(); } } catch (e) { /* no-op */ }
        initValidate();
        initDataTable();
        initFilters();
        initButtons();
        setView('list');
        loadSpaces();
    });
})();
