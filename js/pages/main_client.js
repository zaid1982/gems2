function MainClient() {

    const className = 'MainClient';
    const momentAvailable = (typeof moment === 'function');
    let self = this;
    let versionLocal;
    let modalConfirmDeleteClass;
    let refStatus;
    let refSeverity;
    let refFailureCode;
    let oTableClient;
    let modalClientClass;
    let modalSeverityHourClass;
    let clientDataCache = [];
    let lastListUpdated = null;
    let statusFilterValue = '';
    let statusFilterFn;

    const statusChipMap = {
        '': '#linkClnAll',
        '1': '#linkClnActive',
        '2': '#linkClnInactive',
        '5': '#linkClnArchived'
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

    function refreshListSummary() {
        if (!oTableClient) {
            return;
        }
        const info = (typeof oTableClient.page === 'function' && typeof oTableClient.page.info === 'function')
            ? oTableClient.page.info()
            : null;
        const showing = info ? (info.end - info.start) : 0;
        const total = info ? info.recordsDisplay : 0;
        const summaryText = 'Showing ' + mzFormatNumber(showing, 0) + ' of ' + mzFormatNumber(total, 0);
        const updatedText = formatTimestamp(lastListUpdated);
        $('#lblClnFilterCount').text(summaryText);
        $('#lblClnListCount').text(summaryText + ' results');
        $('#lblClnFilterUpdated').text(updatedText);
        $('#lblClnListUpdated').html('<i class="far fa-clock me-1"></i>' + updatedText);
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

    function updateClientMetrics(dataSet) {
        let total = 0;
        let active = 0;
        let inactive = 0;
        let archived = 0;
        let configuredKpi = 0;

        (dataSet || []).forEach(function (item) {
            if (!item) {
                return;
            }
            total += 1;
            const status = String(item['clientStatus']);
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
            if (item['severities'] && item['severities'] !== '' && item['severityHours'] && item['severityHours'] !== '') {
                configuredKpi += 1;
            }
        });

        $('#metricClnTotal').text(mzFormatNumber(total, 0));
        $('#metricClnActive').text(mzFormatNumber(active, 0));
        $('#metricClnInactive').text(mzFormatNumber(inactive, 0)).toggleClass('text-warning', inactive > 0);
        $('#metricClnWithKpi').text(mzFormatNumber(configuredKpi, 0)).toggleClass('text-success', configuredKpi > 0);

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
        if (oTableClient) {
            oTableClient.draw();
            refreshListSummary();
        }
        setActiveStatusChip(statusFilterValue);
        if (!fromSelect) {
            $('#optClnStatus').val(statusFilterValue);
        }
    }

    function handleStatusSelectChange() {
        setStatusFilter($(this).val() || '', true);
    }

    function severityName(severityId) {
        if (refSeverity && refSeverity[severityId] && refSeverity[severityId]['severityName']) {
            return refSeverity[severityId]['severityName'];
        }
        return 'Severity ' + severityId;
    }

    function failureCodeName(failureCodeId) {
        if (refFailureCode && refFailureCode[failureCodeId] && refFailureCode[failureCodeId]['failureCodeName']) {
            return refFailureCode[failureCodeId]['failureCodeName'];
        }
        return 'Failure Code ' + failureCodeId;
    }

    function renderSeverityColumn(row, metaRow) {
        const severity = row['severities'] || '';
        const severityHour = row['severityHours'] || '';
        const severityRespondTime = row['severityRespondTime'] || '';
        if (!severity || !severityHour) {
            return '<span class="text-muted">Not configured</span>';
        }
        const severitySplit = severity.split(',');
        const hourSplit = severityHour.split(',');
        const respondSplit = severityRespondTime.split(',');
        const listItems = [];
        for (let j = 0; j < severitySplit.length; j++) {
            const severityId = severitySplit[j];
            const hourValue = hourSplit[j] || '0';
            const respondValue = respondSplit[j] || '0';
            const linkId = 'lnkClnClientHourEdit_' + metaRow + '_' + severityId + '__' + hourValue + '___' + respondValue;
            listItems.push(
                '<li>' + GemsUI.escape(severityName(severityId)) + ' - ' + GemsUI.escape(respondValue) + '-minute/' + GemsUI.escape(hourValue) + '-hour ' +
                GemsUI.actionBtn({
                    tint: 'gems-btn-action-edit',
                    cls: 'lnkClnClientHourEdit',
                    id: linkId,
                    title: 'Edit KPI hours',
                    icon: 'fas fa-pen-alt',
                    extra: 'data-severity-id="' + severityId + '" data-severity-hour="' + hourValue + '" data-severity-respond="' + respondValue + '"'
                }) +
                '</li>'
            );
        }
        return '<ul class="list-unstyled mb-0">' + listItems.join('') + '</ul>';
    }

    function getSeverityText(row) {
        const severity = row['severities'] || '';
        const severityHour = row['severityHours'] || '';
        const severityRespondTime = row['severityRespondTime'] || '';
        if (!severity || !severityHour) {
            return 'Not configured';
        }
        const severitySplit = severity.split(',');
        const hourSplit = severityHour.split(',');
        const respondSplit = severityRespondTime ? severityRespondTime.split(',') : [];
        const parts = [];
        for (let j = 0; j < severitySplit.length; j++) {
            const severityId = severitySplit[j];
            const hourValue = hourSplit[j] || '0';
            const respondValue = respondSplit[j] || '0';
            parts.push(severityName(severityId) + ': ' + respondValue + ' min / ' + hourValue + ' hr');
        }
        return parts.join('; ');
    }

    function renderFailureCodesColumn(row) {
        const failureCode = row['failureCodes'] || '';
        if (!failureCode) {
            return '<span class="text-muted">No failure codes</span>';
        }
        const failureCodeSplit = failureCode.split(',');
        const listItems = [];
        for (let j = 0; j < failureCodeSplit.length; j++) {
            listItems.push('<li>' + GemsUI.escape(failureCodeName(failureCodeSplit[j])) + '</li>');
        }
        return '<ul class="list-unstyled mb-0">' + listItems.join('') + '</ul>';
    }

    function getFailureCodesText(row) {
        const failureCode = row['failureCodes'] || '';
        if (!failureCode) {
            return 'No failure codes';
        }
        const failureCodeSplit = failureCode.split(',');
        const names = [];
        for (let j = 0; j < failureCodeSplit.length; j++) {
            names.push(failureCodeName(failureCodeSplit[j]));
        }
        return names.join(', ');
    }

    function rowIdFromLink(el) {
        const linkId = $(el).attr('id') || '';
        const linkIndex = linkId.indexOf('_');
        return linkIndex > 0 ? linkId.substr(linkIndex + 1) : '';
    }

    function rowDataFromLink(el) {
        const rowId = rowIdFromLink(el);
        if (!rowId || !oTableClient) {
            return null;
        }
        return { rowId: rowId, data: oTableClient.row(parseInt(rowId, 10)).data() };
    }

    this.init = function () {
        let exportCounter = 1;
        const exportOpt = {
            columns: [0, 1, 2, 3, 4, 5],
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
        const dtButtons = GemsUI.dtButtons('GEMS 2.0 - Client List').map(function (src) {
            if (src.extend === 'colvis') {
                return src;
            }
            const btn = $.extend(true, {}, src);
            btn.exportOptions = exportOpt;
            return btn;
        });

        oTableClient = $('#dtClnClient').DataTable({
            bLengthChange: false,
            searching: true,
            autoWidth: false,
            aaSorting: [[1, 'asc']],
            dom: GemsUI.dtDomButtons,
            buttons: dtButtons,
            language: GemsUI.dtEmpty('fa-building', 'No clients recorded yet.', 'No clients match the current search or status filter.'),
            pagingType: 'simple_numbers',
            columnDefs: [
                {targets: [0, 5, 6], orderable: false, className: 'text-center'},
                {targets: [1], className: 'text-nowrap'}
            ],
            fnRowCallback: function (nRow, aData, iDisplayIndex) {
                const info = (oTableClient && oTableClient.page && typeof oTableClient.page.info === 'function')
                    ? oTableClient.page.info()
                    : null;
                const rowNumber = info ? (info.start + (iDisplayIndex + 1)) : (iDisplayIndex + 1);
                $('td', nRow).eq(0).html(rowNumber);
            },
            drawCallback: function () {
                refreshListSummary();
            },
            aoColumns: [
                {mData: null, bSortable: false},
                {mData: 'clientName', sClass: 'text-nowrap',
                    mRender: function (data, type) {
                        return displayText(data, type);
                    }
                },
                {mData: 'clientDesc',
                    mRender: function (data, type) {
                        return displayText(data, type);
                    }
                },
                {mData: null,
                    mRender: function (data, type, row, meta) {
                        if (type !== 'display') {
                            return getSeverityText(row);
                        }
                        return renderSeverityColumn(row, meta.row);
                    }
                },
                {mData: null,
                    mRender: function (data, type, row) {
                        if (type !== 'display') {
                            return getFailureCodesText(row);
                        }
                        return renderFailureCodesColumn(row);
                    }
                },
                {mData: null, bSortable: false,
                    mRender: function (data, type, row) {
                        return statusBadge(row['clientStatus'], type);
                    }
                },
                {mData: null, bSortable: false, sClass: 'text-center text-nowrap noVis',
                    mRender: function (data, type, row, meta) {
                        let html = GemsUI.actionBtn({
                            tint: 'gems-btn-action-edit',
                            cls: 'lnkClnClientEdit',
                            id: 'lnkClnClientEdit_' + meta.row,
                            title: 'Edit',
                            icon: 'fas fa-pen-to-square'
                        });
                        if (row['clientStatus'] === '1') {
                            html += GemsUI.actionBtn({
                                tint: 'gems-btn-action-delete',
                                cls: 'lnkClnClientDeactivate',
                                id: 'lnkClnClientDeactivate_' + meta.row,
                                title: 'Deactivate',
                                icon: 'fas fa-toggle-off'
                            });
                        } else {
                            html += GemsUI.actionBtn({
                                tint: 'gems-btn-action-view',
                                cls: 'lnkClnClientActivate',
                                id: 'lnkClnClientActivate_' + meta.row,
                                title: 'Activate',
                                icon: 'fas fa-toggle-on'
                            });
                        }
                        html += GemsUI.actionBtn({
                            tint: 'gems-btn-action-delete',
                            cls: 'lnkClnClientDelete',
                            id: 'lnkClnClientDelete_' + meta.row,
                            title: 'Delete',
                            icon: 'fas fa-trash-alt'
                        });
                        return html;
                    }
                },
                {mData: 'clientStatus', visible: false, sClass: 'noVis'},
                {mData: 'clientId', visible: false, sClass: 'noVis'}
            ]
        });

        oTableClient.buttons().container().appendTo($('#btnDtClnClientExport'));
        GemsUI.bindDtTooltips('#dtClnClient');

        const tbody = $('#dtClnClient tbody');
        tbody.on('click', '.lnkClnClientEdit', function () {
            const current = rowDataFromLink(this);
            if (current && current.data) {
                modalClientClass.edit(current.data['clientId'], current.rowId);
            }
        });
        tbody.on('click', '.lnkClnClientDeactivate', function () {
            const current = rowDataFromLink(this);
            if (current && current.data) {
                modalClientClass.deactivate(current.data['clientId'], current.rowId);
            }
        });
        tbody.on('click', '.lnkClnClientActivate', function () {
            const current = rowDataFromLink(this);
            if (current && current.data) {
                modalClientClass.activate(current.data['clientId'], current.rowId);
            }
        });
        tbody.on('click', '.lnkClnClientDelete', function () {
            const current = rowDataFromLink(this);
            if (current && current.data) {
                modalConfirmDeleteClass.delete(current.data['clientId'], modalClientClass);
            }
        });
        tbody.on('click', '.lnkClnClientHourEdit', function () {
            const row = oTableClient.row($(this).closest('tr'));
            const rowData = row.data();
            if (!rowData) {
                return;
            }
            const passParam = {
                clientName: rowData['clientName'],
                severityId: $(this).data('severity-id'),
                severityRespondTime: $(this).data('severity-respond'),
                severityHour: $(this).data('severity-hour')
            };
            modalSeverityHourClass.edit(rowData['clientId'], row.index(), passParam);
        });

        statusFilterFn = function (settings, data, dataIndex) {
            if (!settings.nTable || settings.nTable.id !== 'dtClnClient') {
                return true;
            }
            if (!statusFilterValue) {
                return true;
            }
            const rowData = oTableClient.row(dataIndex).data();
            if (!rowData) {
                return true;
            }
            return String(rowData['clientStatus']) === statusFilterValue;
        };
        $.fn.dataTable.ext.search.push(statusFilterFn);

        $('#txtClnClientSearch').on('keyup change', function () {
            oTableClient.search($(this).val()).draw();
        });

        $('#optClnStatus').on('change', handleStatusSelectChange);

        $('#btnClnClientAdd').on('click', function () {
            modalClientClass.add();
        });

        $('#btnDtClnClientRefresh').on('click', function () {
            ShowLoader();
            setTimeout(function () {
                try {
                    self.genTableCln();
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

        self.genTableCln();
    };

    this.genTableCln = function () {
        const refClient = mzAjaxRequest('client.php?type=with_severity', 'GET');
        clientDataCache = Array.isArray(refClient) ? refClient : [];
        lastListUpdated = new Date();
        oTableClient.clear().rows.add(clientDataCache).draw();
        updateClientMetrics(clientDataCache);
        refreshListSummary();
        setStatusFilter(statusFilterValue || '', true);
    };

    this.addTableCln = function (_dataAdd) {
        oTableClient.row.add(_dataAdd).draw();
        clientDataCache = oTableClient.rows().data().toArray();
        lastListUpdated = new Date();
        updateClientMetrics(clientDataCache);
        refreshListSummary();
        setStatusFilter(statusFilterValue || '', true);
    };

    this.updateTableCln = function (_dataEdit, _rowEdit) {
        const currentRow = oTableClient.row(_rowEdit).data();
        if (!currentRow) {
            return;
        }
        if (typeof _dataEdit['clientName'] !== 'undefined') {
            currentRow['clientName'] = _dataEdit['clientName'];
        }
        if (typeof _dataEdit['clientDesc'] !== 'undefined') {
            currentRow['clientDesc'] = _dataEdit['clientDesc'];
        }
        if (typeof _dataEdit['clientStatus'] !== 'undefined') {
            currentRow['clientStatus'] = _dataEdit['clientStatus'];
        }
        if (typeof _dataEdit['severities'] !== 'undefined') {
            currentRow['severities'] = _dataEdit['severities'];
        }
        if (typeof _dataEdit['severityHours'] !== 'undefined') {
            currentRow['severityHours'] = _dataEdit['severityHours'];
        }
        if (typeof _dataEdit['severityRespondTime'] !== 'undefined') {
            currentRow['severityRespondTime'] = _dataEdit['severityRespondTime'];
        }
        if (typeof _dataEdit['failureCodes'] !== 'undefined') {
            currentRow['failureCodes'] = _dataEdit['failureCodes'];
        }
        lastListUpdated = new Date();
        oTableClient.row(_rowEdit).data(currentRow).draw();
        clientDataCache = oTableClient.rows().data().toArray();
        updateClientMetrics(clientDataCache);
        refreshListSummary();
        setStatusFilter(statusFilterValue || '', true);
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

    this.setRefSeverity = function (_refSeverity) {
        refSeverity = _refSeverity;
    };

    this.setRefFailureCode = function (_refFailureCode) {
        refFailureCode = _refFailureCode;
    };

    this.setModalClientClass = function (_modalClientClass) {
        modalClientClass = _modalClientClass;
    };

    this.setModalSeverityHourClass = function (_modalSeverityHourClass) {
        modalSeverityHourClass = _modalSeverityHourClass;
    };

    this.setModalConfirmDeleteClass = function (_modalConfirmDeleteClass) {
        modalConfirmDeleteClass = _modalConfirmDeleteClass;
    };
}
