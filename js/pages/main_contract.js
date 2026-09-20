function MainContract() {

    const className = 'MainContract';
    const momentAvailable = (typeof moment === 'function');
    let self = this;
    let versionLocal;
    let modalConfirmDeleteClass;
    let refStatus;
    let refClient;
    let refSite;
    let oTableContract;
    let modalContractClass;
    let sectionContractClass;
    let contractDataCache = [];
    let lastListUpdated = null;
    let statusFilterValue = '';
    let clientFilterValue = '';
    let siteFilterValue = '';

    const statusChipMap = {
        '': '#linkCcrAll',
        '1': '#linkCcrActive',
        '2': '#linkCcrInactive',
        '5': '#linkCcrArchived'
    };

    function formatTimestamp(value) {
        if (!value) {
            return 'Updated —';
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

    function rowsFromRef(ref, idKey, labelKey, predicate) {
        const rows = [];
        $.each(ref || {}, function (key, item) {
            if (!item || typeof item !== 'object') {
                return true;
            }
            const row = $.extend({}, item);
            if (row[idKey] === undefined || row[idKey] === null || row[idKey] === '') {
                row[idKey] = key;
            }
            if (!row[labelKey] && (row[idKey] === undefined || row[idKey] === '')) {
                return true;
            }
            if (predicate && !predicate(row)) {
                return true;
            }
            rows.push(row);
            return true;
        });
        rows.sort(function (a, b) {
            return String(a[labelKey] || '').localeCompare(String(b[labelKey] || ''));
        });
        return rows;
    }

    function clientRows() {
        return rowsFromRef(refClient, 'clientId', 'clientName');
    }

    function siteRows(clientId) {
        return rowsFromRef(refSite, 'siteId', 'siteName', function (row) {
            if (clientId && String(row['clientId']) !== String(clientId)) {
                return false;
            }
            return true;
        });
    }

    function statusRows() {
        const rows = [];
        ['1', '2', '5'].forEach(function (statusId) {
            if (!refStatus || !refStatus[statusId]) {
                return;
            }
            const row = $.extend({}, refStatus[statusId]);
            if (row['statusId'] === undefined || row['statusId'] === null || row['statusId'] === '') {
                row['statusId'] = statusId;
            }
            rows.push(row);
        });
        return rows;
    }

    function populateStatusFilter() {
        GemsUI.fillSelect(
            'optCcrStatus',
            statusRows(),
            'statusId',
            function (row) {
                return row['statusDesc'] || '';
            },
            'All Status',
            statusFilterValue
        );
    }

    function populateClientFilter() {
        GemsUI.fillSelect(
            'optCcrClientId',
            clientRows(),
            'clientId',
            function (row) {
                return row['clientName'] || '';
            },
            'All Clients',
            clientFilterValue
        );
    }

    function populateSiteFilter() {
        const rows = siteRows(clientFilterValue);
        if (siteFilterValue && !rows.some(function (site) {
            return String(site['siteId']) === String(siteFilterValue);
        })) {
            siteFilterValue = '';
        }
        GemsUI.fillSelect(
            'optCcrSiteId',
            rows,
            'siteId',
            function (row) {
                return row['siteName'] || '';
            },
            'All Sites',
            siteFilterValue
        );
    }

    function getClientName(clientId) {
        if (refClient && refClient[clientId] && refClient[clientId]['clientName']) {
            return refClient[clientId]['clientName'];
        }
        return 'Unknown Client';
    }

    function getSiteName(siteId) {
        if (refSite && refSite[siteId] && refSite[siteId]['siteName']) {
            return refSite[siteId]['siteName'];
        }
        return 'Unknown Site';
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
                return fallback || 'Unknown';
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

    function refreshListSummary() {
        if (!oTableContract) {
            return;
        }
        const info = (typeof oTableContract.page === 'function' && typeof oTableContract.page.info === 'function')
            ? oTableContract.page.info()
            : null;
        const showing = info ? (info.end - info.start) : 0;
        const total = info ? info.recordsDisplay : 0;
        const summaryText = 'Showing ' + mzFormatNumber(showing, 0) + ' of ' + mzFormatNumber(total, 0);
        const updatedText = formatTimestamp(lastListUpdated);
        $('#lblCcrFilterCount').text(summaryText);
        $('#lblCcrListCount').text(summaryText + ' results');
        $('#lblCcrFilterUpdated').text(updatedText);
        $('#lblCcrListUpdated').html('<i class="far fa-clock me-1"></i>' + updatedText);
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

    function getMoment() {
        return momentAvailable ? moment : null;
    }

    function updateContractMetrics(dataSet) {
        let total = 0;
        let active = 0;
        let expiringSoon = 0;
        let ended = 0;
        const now = getMoment() ? getMoment()() : null;

        (dataSet || []).forEach(function (item) {
            if (!item) {
                return;
            }
            total += 1;
            const status = String(item['contractStatus']);
            if (status === '1') {
                active += 1;
            }
            const endDate = item['contractDateEnd'];
            if (endDate) {
                if (now) {
                    const diffDays = getMoment()(endDate, 'YYYY-MM-DD').diff(now, 'days');
                    if (diffDays < 0) {
                        ended += 1;
                    } else if (diffDays <= 30) {
                        expiringSoon += 1;
                    }
                } else {
                    const end = new Date(endDate);
                    const today = new Date();
                    const diff = Math.floor((end - today) / (1000 * 60 * 60 * 24));
                    if (diff < 0) {
                        ended += 1;
                    } else if (diff <= 30) {
                        expiringSoon += 1;
                    }
                }
            }
        });

        $('#metricCcrTotal').text(mzFormatNumber(total, 0));
        $('#metricCcrActive').text(mzFormatNumber(active, 0));
        $('#metricCcrExpiring').text(mzFormatNumber(expiringSoon, 0)).toggleClass('text-warning', expiringSoon > 0);
        $('#metricCcrEnded').text(mzFormatNumber(ended, 0)).toggleClass('text-danger', ended > 0);

        const inactive = (dataSet || []).filter(function (item) {
            return String(item['contractStatus']) === '2';
        }).length;
        const archived = (dataSet || []).filter(function (item) {
            return String(item['contractStatus']) === '5';
        }).length;

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
        if (oTableContract) {
            oTableContract.draw();
            refreshListSummary();
        }
        setActiveStatusChip(statusFilterValue);
        if (!fromSelect) {
            $('#optCcrStatus').val(statusFilterValue);
        }
    }

    function setClientFilter(value, skipSelect) {
        clientFilterValue = value || '';
        if (clientFilterValue && siteFilterValue && refSite && refSite[siteFilterValue]) {
            const siteClientId = refSite[siteFilterValue]['clientId'];
            if (String(siteClientId) !== String(clientFilterValue)) {
                siteFilterValue = '';
            }
        }
        if (oTableContract) {
            oTableContract.draw();
            refreshListSummary();
        }
        populateSiteFilter();
        if (!skipSelect) {
            $('#optCcrClientId').val(clientFilterValue);
        }
    }

    function setSiteFilter(value, skipSelect) {
        siteFilterValue = value || '';
        if (oTableContract) {
            oTableContract.draw();
            refreshListSummary();
        }
        if (!skipSelect) {
            $('#optCcrSiteId').val(siteFilterValue);
        }
    }

    function handleStatusSelectChange() {
        setStatusFilter($(this).val() || '', true);
    }

    function handleClientSelectChange() {
        setClientFilter($(this).val() || '', true);
    }

    function handleSiteSelectChange() {
        setSiteFilter($(this).val() || '', true);
    }

    function rowIdFromLink(el) {
        const linkId = $(el).attr('id') || '';
        const linkIndex = linkId.indexOf('_');
        return linkIndex > 0 ? linkId.substr(linkIndex + 1) : '';
    }

    function rowDataFromLink(el) {
        const rowId = rowIdFromLink(el);
        if (!rowId || !oTableContract) {
            return null;
        }
        return { rowId: rowId, data: oTableContract.row(parseInt(rowId, 10)).data() };
    }

    function contractFilterFn(settings, data, dataIndex) {
        if (!oTableContract || !settings.nTable || settings.nTable.id !== 'dtCcrContract') {
            return true;
        }
        const rowData = oTableContract.row(dataIndex).data();
        if (!rowData) {
            return true;
        }
        if (statusFilterValue && String(rowData['contractStatus']) !== statusFilterValue) {
            return false;
        }
        if (clientFilterValue && String(rowData['clientId']) !== clientFilterValue) {
            return false;
        }
        if (siteFilterValue && String(rowData['siteId']) !== siteFilterValue) {
            return false;
        }
        return true;
    }

    this.init = function () {
        populateStatusFilter();
        populateClientFilter();
        populateSiteFilter();

        let exportCounter = 1;
        const exportOpt = {
            columns: [0, 1, 2, 3, 4, 5, 6, 7],
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
        const dtButtons = GemsUI.dtButtons('GEMS 2.0 - Contract List').map(function (src) {
            if (src.extend === 'colvis') {
                return src;
            }
            const btn = $.extend(true, {}, src);
            btn.exportOptions = exportOpt;
            return btn;
        });

        oTableContract = $('#dtCcrContract').DataTable({
            bLengthChange: false,
            searching: true,
            autoWidth: false,
            aaSorting: [[1, 'asc'], [2, 'asc'], [3, 'asc']],
            dom: GemsUI.dtDomButtons,
            buttons: dtButtons,
            language: GemsUI.dtEmpty('fa-file-contract', 'No contracts recorded yet.', 'No contracts match the current search or filter.'),
            pagingType: 'simple_numbers',
            columnDefs: [
                { targets: [0, 7, 8], orderable: false, className: 'text-center' },
                { targets: [1, 2, 3], className: 'text-nowrap' }
            ],
            fnRowCallback: function (nRow, aData, iDisplayIndex) {
                const info = (oTableContract && oTableContract.page && typeof oTableContract.page.info === 'function')
                    ? oTableContract.page.info()
                    : null;
                const rowNumber = info ? (info.start + (iDisplayIndex + 1)) : (iDisplayIndex + 1);
                $('td', nRow).eq(0).html(rowNumber);
            },
            drawCallback: function () {
                refreshListSummary();
            },
            aoColumns: [
                { mData: null, bSortable: false },
                { mData: 'clientId',
                    mRender: function (data, type) {
                        return displayText(getClientName(data), type);
                    }
                },
                { mData: 'siteId',
                    mRender: function (data, type) {
                        return displayText(getSiteName(data), type);
                    }
                },
                { mData: 'contractName',
                    mRender: function (data, type) {
                        return displayText(data, type);
                    }
                },
                { mData: 'contractDesc',
                    mRender: function (data, type) {
                        if (type !== 'display') {
                            return data || '';
                        }
                        if (!data || String(data).trim() === '') {
                            return '<span class="text-muted">No description</span>';
                        }
                        return GemsUI.escape(data);
                    }
                },
                { mData: 'contractDateStart',
                    mRender: function (data, type) {
                        return displayText(data, type);
                    }
                },
                { mData: 'contractDateEnd',
                    mRender: function (data, type) {
                        return displayText(data, type);
                    }
                },
                { mData: 'contractStatus',
                    mRender: function (data, type) {
                        return statusBadge(data, type);
                    }
                },
                { mData: null, bSortable: false, sClass: 'text-center text-nowrap noVis',
                    mRender: function (data, type, row, meta) {
                        let html = GemsUI.actionBtn({
                            tint: 'gems-btn-action-edit',
                            cls: 'lnkCcrContractEdit',
                            id: 'lnkCcrContractEdit_' + meta.row,
                            title: 'Edit',
                            icon: 'fas fa-pen-to-square'
                        });
                        html += GemsUI.actionBtn({
                            tint: 'gems-btn-action-view',
                            cls: 'lnkCcrContractDetails',
                            id: 'lnkCcrContractDetails_' + meta.row,
                            title: 'Details',
                            icon: 'fas fa-search-plus'
                        });
                        if (row['contractStatus'] === '1') {
                            html += GemsUI.actionBtn({
                                tint: 'gems-btn-action-delete',
                                cls: 'lnkCcrContractDeactivate',
                                id: 'lnkCcrContractDeactivate_' + meta.row,
                                title: 'Deactivate',
                                icon: 'fas fa-toggle-off'
                            });
                        } else {
                            html += GemsUI.actionBtn({
                                tint: 'gems-btn-action-view',
                                cls: 'lnkCcrContractActivate',
                                id: 'lnkCcrContractActivate_' + meta.row,
                                title: 'Activate',
                                icon: 'fas fa-toggle-on'
                            });
                        }
                        html += GemsUI.actionBtn({
                            tint: 'gems-btn-action-delete',
                            cls: 'lnkCcrContractDelete',
                            id: 'lnkCcrContractDelete_' + meta.row,
                            title: 'Delete',
                            icon: 'fas fa-trash-alt'
                        });
                        return html;
                    }
                }
            ]
        });

        oTableContract.buttons().container().appendTo($('#btnDtCcrContractExport'));
        GemsUI.bindDtTooltips('#dtCcrContract');

        $.fn.dataTable.ext.search.push(contractFilterFn);

        const tbody = $('#dtCcrContract tbody');
        tbody.on('click', '.lnkCcrContractEdit', function () {
            const current = rowDataFromLink(this);
            if (current && current.data) {
                modalContractClass.edit(current.data['contractId'], current.rowId);
            }
        });
        tbody.on('click', '.lnkCcrContractDetails', function () {
            const current = rowDataFromLink(this);
            if (current && current.data) {
                sectionContractClass.load(current.data['contractId'], current.rowId);
            }
        });
        tbody.on('click', '.lnkCcrContractDeactivate', function () {
            const current = rowDataFromLink(this);
            if (current && current.data) {
                modalContractClass.deactivate(current.data['contractId'], current.rowId);
            }
        });
        tbody.on('click', '.lnkCcrContractActivate', function () {
            const current = rowDataFromLink(this);
            if (current && current.data) {
                modalContractClass.activate(current.data['contractId'], current.rowId);
            }
        });
        tbody.on('click', '.lnkCcrContractDelete', function () {
            const current = rowDataFromLink(this);
            if (current && current.data) {
                modalConfirmDeleteClass.delete(current.data['contractId'], modalContractClass);
            }
        });

        $('#txtCcrContractSearch').on('keyup change', function () {
            oTableContract.search($(this).val()).draw();
        });

        $('#optCcrStatus').on('change', handleStatusSelectChange);
        $('#optCcrClientId').on('change', handleClientSelectChange);
        $('#optCcrSiteId').on('change', handleSiteSelectChange);

        $('#btnCcrContractAdd').on('click', function () {
            modalContractClass.add();
        });

        $('#btnDtCcrContractRefresh').on('click', function () {
            ShowLoader();
            setTimeout(function () {
                try {
                    self.genTableCcr(1);
                } catch (e) {
                    toastr['error'](e.message, _ALERT_TITLE_ERROR);
                }
                HideLoader();
            }, 300);
        });

        $.each(statusChipMap, function (status, selector) {
            $(selector).off('click').on('click', function () {
                setStatusFilter(status, false);
            });
        });

        setStatusFilter('', true);
        setClientFilter('', true);
        setSiteFilter('', true);

        self.genTableCcr(0);
    };

    this.genTableCcr = function (_type) {
        if (_type === 1) {
            versionLocal = mzGetDataVersion();
        }
        const refContract = mzGetLocalRaw('gems_contract', versionLocal, [], 'contract');
        contractDataCache = Array.isArray(refContract) ? refContract : [];
        lastListUpdated = new Date();
        oTableContract.clear().rows.add(contractDataCache).draw();
        contractDataCache = oTableContract.rows().data().toArray();
        updateContractMetrics(contractDataCache);
        refreshListSummary();
        setStatusFilter(statusFilterValue || '', true);
        setClientFilter(clientFilterValue || '', true);
        setSiteFilter(siteFilterValue || '', true);
    };

    this.addTableCcr = function (_dataAdd) {
        oTableContract.row.add(_dataAdd).draw();
        contractDataCache = oTableContract.rows().data().toArray();
        lastListUpdated = new Date();
        updateContractMetrics(contractDataCache);
        refreshListSummary();
        setStatusFilter(statusFilterValue || '', true);
        setClientFilter(clientFilterValue || '', true);
        setSiteFilter(siteFilterValue || '', true);
    };

    this.updateTableCcr = function (_dataEdit, _rowEdit) {
        const currentRow = oTableContract.row(_rowEdit).data();
        if (!currentRow) {
            return;
        }
        if (typeof _dataEdit['contractName'] !== 'undefined') {
            currentRow['contractName'] = _dataEdit['contractName'];
        }
        if (typeof _dataEdit['contractDesc'] !== 'undefined') {
            currentRow['contractDesc'] = _dataEdit['contractDesc'];
        }
        if (typeof _dataEdit['contractDateStart'] !== 'undefined') {
            currentRow['contractDateStart'] = _dataEdit['contractDateStart'];
        }
        if (typeof _dataEdit['contractDateEnd'] !== 'undefined') {
            currentRow['contractDateEnd'] = _dataEdit['contractDateEnd'];
        }
        if (typeof _dataEdit['contractStatus'] !== 'undefined') {
            currentRow['contractStatus'] = _dataEdit['contractStatus'];
        }
        if (typeof _dataEdit['clientId'] !== 'undefined') {
            currentRow['clientId'] = _dataEdit['clientId'];
        }
        if (typeof _dataEdit['siteId'] !== 'undefined') {
            currentRow['siteId'] = _dataEdit['siteId'];
        }
        oTableContract.row(_rowEdit).data(currentRow).draw();
        contractDataCache = oTableContract.rows().data().toArray();
        lastListUpdated = new Date();
        updateContractMetrics(contractDataCache);
        refreshListSummary();
        setStatusFilter(statusFilterValue || '', true);
        setClientFilter(clientFilterValue || '', true);
        setSiteFilter(siteFilterValue || '', true);
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

    this.setRefSite = function (_refSite) {
        refSite = _refSite;
    };

    this.setModalContractClass = function (_modalContractClass) {
        modalContractClass = _modalContractClass;
    };

    this.setSectionContractClass = function (_sectionContractClass) {
        sectionContractClass = _sectionContractClass;
    };

    this.setModalConfirmDeleteClass = function (_modalConfirmDeleteClass) {
        modalConfirmDeleteClass = _modalConfirmDeleteClass;
    };
}
