function MainSite() {

    const className = 'MainSite';
    const momentAvailable = (typeof moment === 'function');
    let self = this;
    let versionLocal;
    let refStatus;
    let refClient;
    let oTableSite;
    let modalSiteClass;
    let modalConfirmDeleteClass;
    let siteDataCache = [];
    let lastListUpdated = null;
    let statusFilterValue = '';
    let clientFilterValue = '';
    let statusFilterFn;

    const statusChipMap = {
        '': '#linkSteAll',
        '1': '#linkSteActive',
        '2': '#linkSteInactive',
        '5': '#linkSteArchived'
    };

    function formatTimestamp(value) {
        if (!value) {
            return '—';
        }
        if (momentAvailable) {
            return 'Updated ' + moment(value).format('DD MMM YYYY, hh:mm A');
        }
        return 'Updated ' + new Date(value).toLocaleString();
    }

    function displayText(value, type) {
        const text = value || '';
        if (type !== 'display') {
            return text;
        }
        return GemsUI.escape(text);
    }

    function clientRows() {
        const rows = [];
        if (refClient) {
            $.each(refClient, function (key, client) {
                if (!client || typeof client !== 'object') {
                    return true;
                }
                rows.push(client);
                return true;
            });
        }
        rows.sort(function (a, b) {
            return (a['clientName'] || '').localeCompare(b['clientName'] || '');
        });
        return rows;
    }

    function populateClientFilter() {
        GemsUI.fillSelect(
            'optSteClientId',
            clientRows(),
            'clientId',
            function (row) {
                return row['clientName'] || '';
            },
            'All Clients',
            clientFilterValue
        );
    }

    function getClientName(clientId) {
        if (refClient && refClient[clientId] && refClient[clientId]['clientName']) {
            return refClient[clientId]['clientName'];
        }
        return 'Unknown Client';
    }

    function statusLabel(statusId, fallback) {
        if (refStatus && refStatus[statusId] && refStatus[statusId]['statusDesc']) {
            return refStatus[statusId]['statusDesc'];
        }
        switch (String(statusId)) {
            case '1':
                return 'Active';
            case '2':
                return 'Inactive';
            case '5':
                return 'Archived';
            default:
                return fallback || '';
        }
    }

    function statusBadgeKind(status) {
        switch (String(status)) {
            case '1':
                return 'success';
            case '5':
                return 'warning';
            default:
                return 'secondary';
        }
    }

    function statusBadge(statusId, type) {
        const label = statusLabel(statusId, 'Unknown');
        if (type !== 'display') {
            return label;
        }
        return GemsUI.badge(statusBadgeKind(statusId), GemsUI.escape(label));
    }

    function formatYesNo(value) {
        return value === '1' || value === 1 ? 'Yes' : 'No';
    }

    function yesNoBadge(value, type) {
        const label = formatYesNo(value);
        if (type !== 'display') {
            return label;
        }
        return GemsUI.badge(label === 'Yes' ? 'success' : 'secondary', label);
    }

    function refreshListSummary() {
        if (!oTableSite) {
            return;
        }
        const info = (typeof oTableSite.page === 'function' && typeof oTableSite.page.info === 'function')
            ? oTableSite.page.info()
            : null;
        const showing = info ? (info.end - info.start) : 0;
        const total = info ? info.recordsDisplay : 0;
        const summaryText = 'Showing ' + mzFormatNumber(showing, 0) + ' of ' + mzFormatNumber(total, 0);
        const updatedText = formatTimestamp(lastListUpdated);
        $('#lblSteFilterCount').text(summaryText);
        $('#lblSteListCount').text(summaryText + ' results');
        $('#lblSteFilterUpdated').text(updatedText);
        $('#lblSteListUpdated').html('<i class="far fa-clock me-1"></i>' + updatedText);
    }

    function updateStatusChips(counts) {
        const labelMap = {
            '': 'All',
            '1': statusLabel('1', 'Active'),
            '2': statusLabel('2', 'Inactive'),
            '5': statusLabel('5', 'Archived')
        };
        $.each(statusChipMap, function (status, selector) {
            const count = typeof counts[status] !== 'undefined' ? counts[status] : 0;
            $(selector).html(GemsUI.escape(labelMap[status]) + ' <span class="badge bg-secondary-lt ms-1">' + mzFormatNumber(count, 0) + '</span>');
        });
        setActiveStatusChip(statusFilterValue);
    }

    function updateSiteMetrics(dataSet) {
        let total = 0;
        let active = 0;
        let inactive = 0;
        let archived = 0;
        let wrEnabled = 0;
        let publicEnabled = 0;

        (dataSet || []).forEach(function (item) {
            if (!item) {
                return;
            }
            total += 1;
            const status = String(item['siteStatus']);
            switch (status) {
                case '1':
                    active += 1;
                    break;
                case '2':
                    inactive += 1;
                    break;
                case '5':
                    archived += 1;
                    break;
                default:
                    break;
            }
            if (item['siteIsWr'] === '1' || item['siteIsWr'] === 1) {
                wrEnabled += 1;
            }
            if (item['siteIsPublic'] === '1' || item['siteIsPublic'] === 1) {
                publicEnabled += 1;
            }
        });

        $('#metricSteTotal').text(mzFormatNumber(total, 0));
        $('#metricSteActive').text(mzFormatNumber(active, 0));
        $('#metricSteWr').text(mzFormatNumber(wrEnabled, 0)).toggleClass('text-success', wrEnabled > 0);
        $('#metricStePublic').text(mzFormatNumber(publicEnabled, 0)).toggleClass('text-success', publicEnabled > 0);

        updateStatusChips({
            '': total,
            '1': active,
            '2': inactive,
            '5': archived
        });
    }

    function setActiveStatusChip(value) {
        $.each(statusChipMap, function (status, selector) {
            if (status === value) {
                $(selector).addClass('active btn-primary').removeClass('btn-outline-secondary');
            } else {
                $(selector).removeClass('active btn-primary').addClass('btn-outline-secondary');
            }
        });
    }

    function setStatusFilter(value, fromSelect) {
        statusFilterValue = value || '';
        if (oTableSite) {
            oTableSite.draw();
            refreshListSummary();
        }
        setActiveStatusChip(statusFilterValue);
        if (!fromSelect) {
            $('#optSteStatus').val(statusFilterValue);
        }
    }

    function setClientFilter(value, skipSelect) {
        clientFilterValue = value || '';
        if (oTableSite) {
            if (clientFilterValue) {
                oTableSite.column(8).search('^' + clientFilterValue + '$', true, false, true);
            } else {
                oTableSite.column(8).search('');
            }
            oTableSite.draw();
            refreshListSummary();
        }
        if (!skipSelect) {
            $('#optSteClientId').val(clientFilterValue);
        }
    }

    function handleStatusSelectChange() {
        setStatusFilter($(this).val() || '', true);
    }

    function handleClientSelectChange() {
        setClientFilter($(this).val() || '', true);
    }

    function rowIdFromLink(el) {
        const linkId = $(el).attr('id') || '';
        const linkIndex = linkId.indexOf('_');
        return linkIndex > 0 ? linkId.substr(linkIndex + 1) : '';
    }

    function rowDataFromLink(el) {
        const rowId = rowIdFromLink(el);
        if (!rowId || !oTableSite) {
            return null;
        }
        return { rowId: rowId, data: oTableSite.row(parseInt(rowId, 10)).data() };
    }

    this.init = function () {
        populateClientFilter();

        let exportCounter = 1;
        const exportOpt = {
            columns: [0, 1, 2, 3, 4, 5, 6],
            orthogonal: 'export',
            format: {
                body: function (data, row, column) {
                    if (row === 0 && column === 0) {
                        exportCounter = 1;
                    }
                    if (column === 0) {
                        return exportCounter++;
                    }
                    return data;
                }
            }
        };
        const dtButtons = GemsUI.dtButtons('GEMS 2.0 - Site List').map(function (src) {
            if (src.extend === 'colvis') {
                return src;
            }
            const btn = $.extend(true, {}, src);
            btn.exportOptions = exportOpt;
            return btn;
        });

        oTableSite = $('#dtSteSite').DataTable({
            bLengthChange: false,
            searching: true,
            autoWidth: false,
            aaSorting: [[1, 'asc'], [2, 'asc']],
            dom: GemsUI.dtDomButtons,
            buttons: dtButtons,
            language: GemsUI.dtEmpty('fa-map-marker-alt', 'No sites recorded yet.', 'No sites match the current search or filter.'),
            pagingType: 'simple_numbers',
            columnDefs: [
                {targets: [0, 4, 5, 6, 7], orderable: false, className: 'text-center'},
                {targets: [1, 2, 3], className: 'text-nowrap'}
            ],
            fnRowCallback: function (nRow, aData, iDisplayIndex) {
                const info = (oTableSite && oTableSite.page && typeof oTableSite.page.info === 'function')
                    ? oTableSite.page.info()
                    : null;
                const rowNumber = info ? (info.start + (iDisplayIndex + 1)) : (iDisplayIndex + 1);
                $('td', nRow).eq(0).html(rowNumber);
            },
            drawCallback: function () {
                refreshListSummary();
            },
            aoColumns: [
                {mData: null, bSortable: false},
                {mData: 'clientId',
                    mRender: function (data, type) {
                        return displayText(getClientName(data), type);
                    }
                },
                {mData: 'siteName',
                    mRender: function (data, type) {
                        return displayText(data, type);
                    }
                },
                {mData: 'siteCode',
                    mRender: function (data, type) {
                        return displayText(data, type);
                    }
                },
                {mData: 'siteIsWr',
                    mRender: function (data, type) {
                        return yesNoBadge(data, type);
                    }
                },
                {mData: 'siteIsPublic',
                    mRender: function (data, type) {
                        return yesNoBadge(data, type);
                    }
                },
                {mData: null, bSortable: false,
                    mRender: function (data, type, row) {
                        return statusBadge(row['siteStatus'], type);
                    }
                },
                {mData: null, bSortable: false, sClass: 'text-center text-nowrap noVis',
                    mRender: function (data, type, row, meta) {
                        let html = GemsUI.actionBtn({
                            tint: 'gems-btn-action-view',
                            cls: 'lnkSteSiteVisitorPublic',
                            id: 'lnkSteSiteVisitorPublic_' + meta.row,
                            title: 'Visitor Public Link / QR',
                            icon: 'fas fa-qrcode'
                        });
                        html += GemsUI.actionBtn({
                            tint: 'gems-btn-action-view',
                            cls: 'lnkSteSitePtwPublic',
                            id: 'lnkSteSitePtwPublic_' + meta.row,
                            title: 'PTW Public Link / QR',
                            icon: 'fas fa-qrcode'
                        });
                        html += GemsUI.actionBtn({
                            tint: 'gems-btn-action-edit',
                            cls: 'lnkSteSiteEdit',
                            id: 'lnkSteSiteEdit_' + meta.row,
                            title: 'Edit',
                            icon: 'fas fa-pen-to-square'
                        });
                        if (row['siteStatus'] === '1') {
                            html += GemsUI.actionBtn({
                                tint: 'gems-btn-action-delete',
                                cls: 'lnkSteSiteDeactivate',
                                id: 'lnkSteSiteDeactivate_' + meta.row,
                                title: 'Deactivate',
                                icon: 'fas fa-toggle-off'
                            });
                        } else {
                            html += GemsUI.actionBtn({
                                tint: 'gems-btn-action-view',
                                cls: 'lnkSteSiteActivate',
                                id: 'lnkSteSiteActivate_' + meta.row,
                                title: 'Activate',
                                icon: 'fas fa-toggle-on'
                            });
                        }
                        html += GemsUI.actionBtn({
                            tint: 'gems-btn-action-delete',
                            cls: 'lnkSteSiteDelete',
                            id: 'lnkSteSiteDelete_' + meta.row,
                            title: 'Delete',
                            icon: 'fas fa-trash-alt'
                        });
                        return html;
                    }
                },
                {mData: 'clientId', visible: false, sClass: 'noVis'},
                {mData: 'siteStatus', visible: false, sClass: 'noVis'},
                {mData: 'siteId', visible: false, sClass: 'noVis'}
            ]
        });

        oTableSite.buttons().container().appendTo($('#btnDtSteSiteExport'));
        GemsUI.bindDtTooltips('#dtSteSite');

        const tbody = $('#dtSteSite tbody');
        tbody.on('click', '.lnkSteSiteEdit', function () {
            const current = rowDataFromLink(this);
            if (current && current.data) {
                modalSiteClass.edit(current.data['siteId'], current.rowId);
            }
        });
        tbody.on('click', '.lnkSteSiteDeactivate', function () {
            const current = rowDataFromLink(this);
            if (current && current.data) {
                modalSiteClass.deactivate(current.data['siteId'], current.rowId);
            }
        });
        tbody.on('click', '.lnkSteSiteActivate', function () {
            const current = rowDataFromLink(this);
            if (current && current.data) {
                modalSiteClass.activate(current.data['siteId'], current.rowId);
            }
        });
        tbody.on('click', '.lnkSteSiteDelete', function () {
            const current = rowDataFromLink(this);
            if (current && current.data) {
                modalConfirmDeleteClass.delete(current.data['siteId'], modalSiteClass);
            }
        });
        tbody.on('click', '.lnkSteSiteVisitorPublic', function () {
            const current = rowDataFromLink(this);
            if (current && current.data && typeof window.modalSiteVisitorPublicGlobal !== 'undefined') {
                window.modalSiteVisitorPublicGlobal.openForSite(current.data['siteId']);
            }
        });
        tbody.on('click', '.lnkSteSitePtwPublic', function () {
            const current = rowDataFromLink(this);
            if (current && current.data && typeof window.modalSitePtwPublicGlobal !== 'undefined') {
                window.modalSitePtwPublicGlobal.openForSite(current.data['siteId']);
            }
        });

        statusFilterFn = function (settings, data, dataIndex) {
            if (!settings.nTable || settings.nTable.id !== 'dtSteSite') {
                return true;
            }
            if (!statusFilterValue) {
                return true;
            }
            const rowData = oTableSite.row(dataIndex).data();
            if (!rowData) {
                return true;
            }
            return String(rowData['siteStatus']) === statusFilterValue;
        };
        $.fn.dataTable.ext.search.push(statusFilterFn);

        $('#txtSteSiteSearch').on('keyup change', function () {
            oTableSite.search($(this).val()).draw();
        });

        $('#optSteStatus').on('change', handleStatusSelectChange);
        $('#optSteClientId').on('change', handleClientSelectChange);

        $('#btnSteSiteAdd').on('click', function () {
            modalSiteClass.add();
        });

        $('#btnDtSteSiteRefresh').on('click', function () {
            ShowLoader();
            setTimeout(function () {
                try {
                    self.genTableSte(1);
                } catch (e) {
                    toastr['error'](e.message, _ALERT_TITLE_ERROR);
                }
                HideLoader();
            }, 200);
        });

        $.each(statusChipMap, function (status, selector) {
            $(selector).off('click').on('click', function () {
                setStatusFilter(status, false);
            });
        });

        setStatusFilter('', true);
        setClientFilter('', true);

        self.genTableSte(0);
    };

    this.genTableSte = function (_type) {
        if (_type === 1) {
            versionLocal = mzGetDataVersion();
        }
        const refSite = mzGetLocalRaw('gems_site', versionLocal, [], 'site');
        siteDataCache = Array.isArray(refSite) ? refSite : [];
        lastListUpdated = new Date();
        oTableSite.clear().rows.add(siteDataCache).draw();
        updateSiteMetrics(siteDataCache);
        refreshListSummary();
        setStatusFilter(statusFilterValue || '', true);
        setClientFilter(clientFilterValue || '', true);
    };

    this.addTableSte = function (_dataAdd) {
        oTableSite.row.add(_dataAdd).draw();
        siteDataCache = oTableSite.rows().data().toArray();
        lastListUpdated = new Date();
        updateSiteMetrics(siteDataCache);
        refreshListSummary();
        setStatusFilter(statusFilterValue || '', true);
        setClientFilter(clientFilterValue || '', true);
    };

    this.updateTableSte = function (_dataEdit, _rowEdit) {
        const currentRow = oTableSite.row(_rowEdit).data();
        if (!currentRow) {
            return;
        }
        if (typeof _dataEdit['siteName'] !== 'undefined') {
            currentRow['siteName'] = _dataEdit['siteName'];
        }
        if (typeof _dataEdit['siteCode'] !== 'undefined') {
            currentRow['siteCode'] = _dataEdit['siteCode'];
        }
        if (typeof _dataEdit['siteDesc'] !== 'undefined') {
            currentRow['siteDesc'] = _dataEdit['siteDesc'];
        }
        if (typeof _dataEdit['siteIsWr'] !== 'undefined') {
            currentRow['siteIsWr'] = _dataEdit['siteIsWr'];
        }
        if (typeof _dataEdit['siteIsPublic'] !== 'undefined') {
            currentRow['siteIsPublic'] = _dataEdit['siteIsPublic'];
        }
        if (typeof _dataEdit['siteStatus'] !== 'undefined') {
            currentRow['siteStatus'] = _dataEdit['siteStatus'];
        }
        if (typeof _dataEdit['clientId'] !== 'undefined') {
            currentRow['clientId'] = _dataEdit['clientId'];
        }
        lastListUpdated = new Date();
        oTableSite.row(_rowEdit).data(currentRow).draw();
        siteDataCache = oTableSite.rows().data().toArray();
        updateSiteMetrics(siteDataCache);
        refreshListSummary();
        setStatusFilter(statusFilterValue || '', true);
        setClientFilter(clientFilterValue || '', true);
    };

    this.getClassName = function () {
        return className;
    };

    this.setVersionLocal = function (_versionLocal) {
        versionLocal = _versionLocal;
    };

    this.setRefStatus = function (_refStatus) {
        refStatus = _refStatus;
    };

    this.setRefClient = function (_refClient) {
        refClient = _refClient;
    };

    this.setModalSiteClass = function (_modalSiteClass) {
        modalSiteClass = _modalSiteClass;
    };

    this.setModalConfirmDeleteClass = function (_modalConfirmDeleteClass) {
        modalConfirmDeleteClass = _modalConfirmDeleteClass;
    };
}
