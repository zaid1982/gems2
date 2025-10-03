(function() {
    'use strict';

    let dt;
    let currentSiteId = '';

    function fetchSpaces(params) {
        const token = sessionStorage.getItem('token');
        const headers = token ? { 'Authorization': 'Bearer ' + token } : {};
        return $.ajax({ url: 'api/space.php' + buildQuery(params), method: 'GET', dataType: 'json', headers });
    }
    function buildQuery(params) { const q = []; if (params.siteId) q.push('siteId=' + encodeURIComponent(params.siteId)); if (params.status) q.push('status=' + encodeURIComponent(params.status)); return q.length ? ('?' + q.join('&')) : ''; }
    function statusBadge(status) { const map = { AVAILABLE: 'success', ACTIVE: 'info', RESERVED: 'warning', DISABLED: 'danger' }; const cls = map[status] || 'secondary'; return '<span class="badge badge-modern badge-compact badge-' + cls + '">' + status + '</span>'; }

    function initDataTable() {
        const isAdmin = mzIsRoleExist('1,10');
        dt = $('#dtSpc').DataTable({ language: _DATATABLE_LANGUAGE, searching: true, ordering: true, responsive: true, paging: true, columnDefs: [{ targets: 0, orderable: false, searchable: false }, { targets: -1, orderable: false, searchable: false }], order: [[1, 'asc']], data: [], columns: [ { data: null, render: function() { return ''; } }, { data: 'spaceName' }, { data: 'locationName', defaultContent: '' }, { data: 'categoryName', defaultContent: '' }, { data: 'typeName', defaultContent: '' }, { data: 'spaceArea', defaultContent: '' }, { data: 'spaceCapacity', defaultContent: '' }, { data: 'spaceStatus', render: function(d){ return statusBadge(d); } }, { data: null, render: function(row){ 
            let html = '<div class="btn-group btn-group-sm" role="group">';
            if (isAdmin) { html += '<button type="button" class="btn btn-outline-secondary btnSpcManage" title="Manage"><i class="fas fa-tools"></i></button>'; }
            html += '<button type="button" class="btn btn-outline-primary btnSpcView" title="View"><i class="fa fa-eye"></i></button>';
            html += '</div>'; return html; 
        } } ] });
        dt.on('order.dt search.dt', function () { dt.column(0, { search: 'applied', order: 'applied' }).nodes().each(function (cell, i) { cell.innerHTML = i + 1; }); }).draw();
    }

    function loadSpaces() {
        console.log('Loading spaces for site:', currentSiteId, 'status:', $('#optSpcStatus').val());
        ShowLoader(); const status = $('#optSpcStatus').val();
        const params = { siteId: currentSiteId, status: status };
        fetchSpaces(params)
            .done(function(resp){
                if (resp && resp.success) {
                    dt.clear().rows.add(resp.result || []).draw();
                } else {
                    try { console.error('Space list error:', resp); } catch(e) {}
                    alert((resp && resp.errmsg) || _ALERT_MSG_ERROR_DEFAULT);
                }
            })
            .fail(function(jqXHR){
                try { console.error('Space list request failed', jqXHR.status, jqXHR.responseText); } catch(e) {}
                alert(_ALERT_MSG_ERROR_DEFAULT);
            })
            .always(function(){ HideLoader(); });
    }

    function initFilters() {
        const userSite = mzGetUserInfoByParam('siteId'); currentSiteId = userSite || ''; const isAdmin = mzIsRoleExist('1,10');
        if (!isAdmin && userSite) { $('#optSpcSiteId').append('<option value="' + userSite + '" selected>' + mzGetUserInfoByParam('siteName') + '</option>'); $('#optSpcSiteId').prop('disabled', true); } else { $('#optSpcSiteId').append('<option value="">All Sites</option>'); }
        // Destroy existing instances to avoid duplicate dropdowns created by global init
        try { $('#optSpcStatus').materialSelect('destroy'); } catch(e){}
        try { $('#optSpcColumns').materialSelect('destroy'); } catch(e){}
        try { $('#optSpcSiteId').materialSelect('destroy'); } catch(e){}
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

    function initButtons() {
        $('#btnDtSpcRefresh').on('click', loadSpaces);
        $('#btnSpcAdd').on('click', openCreateModal);
    $('#dtSpc').on('click', '.btnSpcView', function(){ const data = dt.row($(this).closest('tr')).data(); if (data && data.spaceId) { window.location.href = 'space_preview.html?id=' + data.spaceId; } });
    $('#btnSpcSave').on('click', saveSpace);
    $('#dtSpc').on('click', '.btnSpcManage', function(){ const data = dt.row($(this).closest('tr')).data(); if (data && data.spaceId) { window.location.href = 'space_manage.html?id=' + data.spaceId; } });
    }

    $(document).ready(function() {
        // Load shared html fragments from html/ directory, then initialize common layout
        let pending = $('.includeHtml').length;
        if (pending === 0) {
            try { if (typeof initiatePages === 'function') { initiatePages(); } } catch(e) { /* no-op */ }
            initDataTable(); initFilters(); initButtons(); loadSpaces();
        } else {
            $('.includeHtml').each(function(){
                const id = $(this).attr('id');
                const target = 'html/' + id.substr(2) + '.html?' + new Date().valueOf();
                $('#'+id).load(target, function(){
                    pending--; if (pending === 0) {
                        try { if (typeof initiatePages === 'function') { initiatePages(); } } catch(e) { /* no-op */ }
                        initDataTable(); initFilters(); initButtons(); loadSpaces();
                    }
                });
            });
        }
    });
})();
